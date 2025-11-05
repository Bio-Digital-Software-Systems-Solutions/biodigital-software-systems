<?php

namespace App\Http\Controllers;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function __construct()
    {
        // Only SuperAdmin can access
        $this->middleware(function ($request, $next) {
            if (!$request->user()->hasRole(RoleEnum::SUPER_ADMIN)) {
                abort(403, 'Access denied. SuperAdmin role required.');
            }
            return $next($request);
        });
    }

    /**
     * Display the user management page
     */
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);

        // Cache users list per page (5 minutes cache)
        $users = CacheService::rememberPaginated(
            'user_management.users',
            $page,
            fn() => User::with(['roles', 'permissions', 'teacher'])->paginate(20),
            CacheService::SHORT_CACHE
        );

        // Cache roles and permissions (1 hour cache)
        $roles = CacheService::remember(
            'user_management.roles',
            fn() => Role::with('permissions')->get(),
            CacheService::MEDIUM_CACHE
        );

        $permissions = CacheService::remember(
            'user_management.permissions',
            fn() => Permission::all(),
            CacheService::MEDIUM_CACHE
        );

        // Cache teachers list (5 minutes cache)
        $teachers = CacheService::remember(
            'user_management.teachers',
            fn() => \App\Models\Teacher::with('user')->get(),
            CacheService::SHORT_CACHE
        );

        return Inertia::render('UserManagement/Index', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Assign roles to a user
     */
    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user->syncRoles($request->roles);

        // Invalidate user management cache
        CacheService::forgetPattern('user_management');

        return response()->json([
            'message' => 'Roles assigned successfully',
            'user' => $user->load(['roles', 'permissions']),
        ]);
    }

    /**
     * Assign permissions to a user
     */
    public function assignPermissions(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $user->syncPermissions($request->permissions);

        return response()->json([
            'message' => 'Permissions assigned successfully',
            'user' => $user->load(['roles', 'permissions']),
        ]);
    }

    /**
     * Create a new role
     */
    public function createRole(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        // Invalidate roles cache
        CacheService::forgetPattern('user_management.roles');
        CacheService::forgetPattern('user_management.permissions');

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role->load('permissions'),
        ]);
    }

    /**
     * Update a role
     */
    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role->load('permissions'),
        ]);
    }

    /**
     * Delete a role
     */
    public function deleteRole(Role $role): JsonResponse
    {
        // Prevent deletion of SuperAdmin role
        if ($role->name === 'SuperAdmin') {
            return response()->json([
                'message' => 'Cannot delete SuperAdmin role',
            ], 403);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }

    /**
     * Create a new permission
     */
    public function createPermission(Request $request): JsonResponse
    {
        // Debug logging
        \Log::info('Permission creation attempt', [
            'request_data' => $request->all(),
            'name_value' => $request->get('name'),
            'user_id' => $request->user()?->id,
        ]);

        try {
            $request->validate([
                'name' => 'required|string|unique:permissions,name',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Permission validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);
            throw $e;
        }

        $permission = Permission::create(['name' => $request->name]);

        // Invalidate permissions cache
        CacheService::forgetPattern('user_management.permissions');

        \Log::info('Permission created successfully', [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
        ]);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission,
        ]);
    }

    /**
     * Delete a permission
     */
    public function deletePermission(Permission $permission): JsonResponse
    {
        $permission->delete();

        // Invalidate permissions cache
        CacheService::forgetPattern('user_management.permissions');

        return response()->json([
            'message' => 'Permission deleted successfully',
        ]);
    }

    /**
     * Get all users (for search/filter)
     */
    public function getUsers(Request $request): JsonResponse
    {
        $query = User::with(['roles', 'permissions']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->paginate(20);

        return response()->json($users);
    }

    /**
     * Toggle user status (activate, deactivate, block, unblock)
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,block,unblock',
            'reason' => 'required|string|min:5|max:500',
        ]);

        // Prevent modification of SuperAdmin users
        if ($user->hasRole(RoleEnum::SUPER_ADMIN)) {
            return response()->json([
                'message' => 'Cannot modify SuperAdmin user status',
            ], 403);
        }

        $updateData = [
            'status_reason' => $request->reason,
            'status_changed_at' => now(),
            'status_changed_by' => $request->user()->id,
        ];

        switch ($request->action) {
            case 'activate':
                $updateData['is_active'] = true;
                $updateData['is_blocked'] = false;
                break;
            case 'deactivate':
                $updateData['is_active'] = false;
                break;
            case 'block':
                $updateData['is_blocked'] = true;
                $updateData['is_active'] = false;
                break;
            case 'unblock':
                $updateData['is_blocked'] = false;
                $updateData['is_active'] = true;
                break;
        }

        $user->update($updateData);

        // Invalidate user cache
        CacheService::forgetPattern('user_management.users');

        return response()->json([
            'message' => 'User status updated successfully',
            'user' => $user->load(['roles', 'permissions']),
        ]);
    }

    /**
     * Delete a user
     */
    public function deleteUser(User $user): JsonResponse
    {
        // Prevent deletion of SuperAdmin users
        if ($user->hasRole(RoleEnum::SUPER_ADMIN)) {
            return response()->json([
                'message' => 'Cannot delete SuperAdmin user',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Show user details
     */
    public function show(User $user): Response
    {
        $user->load(['roles.permissions', 'permissions']);

        // Parse user agent
        $loginInfo = null;
        if ($user->last_login_at) {
            $userAgentData = \App\Helpers\UserAgentHelper::parse($user->last_login_user_agent);
            $loginInfo = [
                'last_login_at' => $user->last_login_at,
                'last_login_ip' => $user->last_login_ip,
                'browser' => $userAgentData['browser'],
                'platform' => $userAgentData['platform'],
            ];
        }

        return Inertia::render('UserManagement/Show', [
            'user' => $user,
            'loginInfo' => $loginInfo,
        ]);
    }

    /**
     * Add a user as a teacher
     */
    public function addTeacher(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'specialization' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
            'bio' => 'nullable|string',
            'qualifications' => 'nullable|array',
            'phone' => 'nullable|string|max:20',
        ]);

        // Check if user is already a teacher
        if ($user->teacher) {
            return response()->json([
                'message' => 'User is already a teacher',
            ], 422);
        }

        $teacher = \App\Models\Teacher::create([
            'user_id' => $user->id,
            'specialization' => $request->specialization,
            'experience_years' => $request->experience_years,
            'bio' => $request->bio,
            'qualifications' => $request->qualifications,
            'phone' => $request->phone,
            'is_active' => true,
        ]);

        // Invalidate user management cache
        CacheService::forgetPattern('user_management');

        return response()->json([
            'message' => 'Teacher added successfully',
            'teacher' => $teacher->load('user'),
        ]);
    }

    /**
     * Remove a teacher
     */
    public function removeTeacher(\App\Models\Teacher $teacher): JsonResponse
    {
        $teacher->delete();

        // Invalidate user management cache
        CacheService::forgetPattern('user_management');

        return response()->json([
            'message' => 'Teacher removed successfully',
        ]);
    }

    /**
     * Update teacher information
     */
    public function updateTeacher(Request $request, \App\Models\Teacher $teacher): JsonResponse
    {
        $request->validate([
            'specialization' => 'nullable|string|max:255',
            'experience_years' => 'nullable|integer|min:0',
            'bio' => 'nullable|string',
            'qualifications' => 'nullable|array',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $teacher->update($request->only([
            'specialization',
            'experience_years',
            'bio',
            'qualifications',
            'phone',
            'is_active',
        ]));

        // Invalidate user management cache
        CacheService::forgetPattern('user_management');

        return response()->json([
            'message' => 'Teacher updated successfully',
            'teacher' => $teacher->load('user'),
        ]);
    }
}
