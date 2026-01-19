import React, { useState, useMemo, useCallback } from 'react';
import DashboardLayout from '@/Layouts/DashboardLayout';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { User, Role, Permission } from '@/Types';
import { PlusIcon, PencilIcon, TrashIcon, UserPlusIcon, ShieldCheckIcon, MagnifyingGlassIcon, XMarkIcon, CheckIcon, XCircleIcon, LockClosedIcon, NoSymbolIcon, Squares2X2Icon, ListBulletIcon, TableCellsIcon, UserIcon, ArrowRightIcon } from '@heroicons/react/24/outline';
import { toast } from 'sonner';
import axios from 'axios';
import { DeleteConfirmationDialog } from '@/Components/ui/delete-confirmation-dialog';
import { Pagination, PaginationData } from '@/Components/ui/pagination';
import { useDebouncedCallback } from 'use-debounce';

interface Teacher {
    id: number;
    uuid: string;
    user_id: number;
    specialization: string | null;
    experience_years: number | null;
    bio: string | null;
    qualifications: string[] | null;
    phone: string | null;
    is_active: boolean;
    user: User;
}

interface Star {
    id: number;
    uuid: string;
    user_id: number;
    title: string | null;
    description: string | null;
    status: string;
    type: string;
    category: string;
    level: number;
    points: number;
    recognition_date: string | null;
    expiry_date: string | null;
    user: User;
}

interface Employee {
    id: number;
    uuid: string;
    user_id: number;
    employee_number: string | null;
    position: string | null;
    job_title: string | null;
    status: string;
    employment_type: string;
    hire_date: string | null;
    user: User;
}

interface Filters {
    search: string;
    role: string;
    per_page: number;
}

interface Props {
    users: PaginationData<UserWithRelations>;
    roles: RoleWithPermissions[];
    permissions: Permission[];
    teachers: Teacher[];
    stars: Star[];
    employees: Employee[];
    filters: Filters;
}

interface UserWithRelations extends User {
    roles: Role[];
    permissions: Permission[];
    is_active?: boolean;
    is_blocked?: boolean;
}

interface RoleWithPermissions extends Role {
    permissions: Permission[];
}

export default function Index({ users, roles, permissions, teachers, stars, employees, filters }: Props) {
    const [selectedUser, setSelectedUser] = useState<UserWithRelations | null>(null);
    const [selectedRole, setSelectedRole] = useState<RoleWithPermissions | null>(null);
    const [activeTab, setActiveTab] = useState<'users' | 'roles' | 'permissions' | 'matrix' | 'teachers' | 'stars' | 'employees'>('users');
    const [showUserRoleDialog, setShowUserRoleDialog] = useState(false);
    const [showUserPermissionDialog, setShowUserPermissionDialog] = useState(false);
    const [showRoleDialog, setShowRoleDialog] = useState(false);
    const [showPermissionDialog, setShowPermissionDialog] = useState(false);
    const [editingRole, setEditingRole] = useState(false);
    const [newRoleName, setNewRoleName] = useState('');
    const [newPermissionName, setNewPermissionName] = useState('');
    const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);
    const [isLoadingUsers, setIsLoadingUsers] = useState(false);

    // Search and filter states - initialized from server filters
    const [userSearch, setUserSearch] = useState(filters?.search || '');
    const [roleFilter, setRoleFilter] = useState(filters?.role || '');

    // Server-side pagination and filtering
    const updateFilters = useCallback((newFilters: Partial<Filters & { page?: number }>) => {
        setIsLoadingUsers(true);
        router.get(
            route('user-management.index'),
            {
                search: newFilters.search ?? userSearch,
                role: newFilters.role ?? roleFilter,
                per_page: newFilters.per_page ?? filters?.per_page ?? 20,
                page: newFilters.page ?? 1,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['users', 'filters'],
                onFinish: () => setIsLoadingUsers(false),
            }
        );
    }, [userSearch, roleFilter, filters?.per_page]);

    // Debounced search to avoid too many requests
    const debouncedSearch = useDebouncedCallback((value: string) => {
        updateFilters({ search: value, page: 1 });
    }, 300);

    const handleSearchChange = (value: string) => {
        setUserSearch(value);
        debouncedSearch(value);
    };

    const handleRoleFilterChange = (value: string) => {
        setRoleFilter(value);
        updateFilters({ role: value, page: 1 });
    };

    const handlePageChange = (page: number) => {
        updateFilters({ page });
    };

    const handlePageSizeChange = (perPage: number) => {
        updateFilters({ per_page: perPage, page: 1 });
    };
    const [roleSearch, setRoleSearch] = useState('');
    const [permissionSearch, setPermissionSearch] = useState('');
    const [matrixSearch, setMatrixSearch] = useState('');
    const [selectedModels, setSelectedModels] = useState<string[]>([]);
    const [teacherSearch, setTeacherSearch] = useState('');
    const [nonTeacherSearch, setNonTeacherSearch] = useState('');
    const [starSearch, setStarSearch] = useState('');
    const [nonStarSearch, setNonStarSearch] = useState('');
    const [employeeSearch, setEmployeeSearch] = useState('');
    const [nonEmployeeSearch, setNonEmployeeSearch] = useState('');
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [userToDelete, setUserToDelete] = useState<string | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [showDeleteRoleDialog, setShowDeleteRoleDialog] = useState(false);
    const [roleToDelete, setRoleToDelete] = useState<number | null>(null);
    const [isDeletingRole, setIsDeletingRole] = useState(false);
    const [showDeletePermissionDialog, setShowDeletePermissionDialog] = useState(false);
    const [permissionToDelete, setPermissionToDelete] = useState<number | null>(null);
    const [isDeletingPermission, setIsDeletingPermission] = useState(false);
    const [showStatusDialog, setShowStatusDialog] = useState(false);
    const [statusAction, setStatusAction] = useState<{ userId: string; action: 'activate' | 'deactivate' | 'block' | 'unblock' } | null>(null);
    const [statusReason, setStatusReason] = useState('');
    const [roleViewMode, setRoleViewMode] = useState<'grid' | 'list'>('grid');
    const [selectedRoleDetail, setSelectedRoleDetail] = useState<RoleWithPermissions | null>(null);
    const [showRoleDetailDialog, setShowRoleDetailDialog] = useState(false);
    const [roleDetailPermissions, setRoleDetailPermissions] = useState<string[]>([]);
    const [userViewMode, setUserViewMode] = useState<'table' | 'list' | 'grid'>('table');

    // Group permissions by model
    const groupedPermissions = useMemo(() => {
        const groups: Record<string, Permission[]> = {};

        permissions.forEach(permission => {
            // Extract the model name from permission (e.g., "view articles" -> "Articles")
            const parts = permission.name.split(' ');
            const model = parts.length > 1 ? parts[parts.length - 1] : 'Other';
            const modelName = model.charAt(0).toUpperCase() + model.slice(1);

            if (!groups[modelName]) {
                groups[modelName] = [];
            }
            groups[modelName].push(permission);
        });

        return groups;
    }, [permissions]);

    // Users are now server-side filtered and paginated
    const filteredUsers = users.data;

    // Filtered roles
    const filteredRoles = useMemo(() => {
        return roles.filter(role =>
            role.name.toLowerCase().includes(roleSearch.toLowerCase())
        );
    }, [roles, roleSearch]);

    // Filtered permissions by search
    const filteredGroupedPermissions = useMemo(() => {
        if (!permissionSearch) return groupedPermissions;

        const filtered: Record<string, Permission[]> = {};
        Object.entries(groupedPermissions).forEach(([model, perms]) => {
            const matchedPerms = perms.filter(p =>
                p.name.toLowerCase().includes(permissionSearch.toLowerCase())
            );
            if (matchedPerms.length > 0) {
                filtered[model] = matchedPerms;
            }
        });
        return filtered;
    }, [groupedPermissions, permissionSearch]);

    const handleAssignRoles = async () => {
        if (!selectedUser) return;

        try {
            await axios.post(route('user-management.assign-roles', selectedUser.uuid), {
                roles: selectedRoles,
            });
            toast.success('Rôles attribués avec succès');
            router.reload({ only: ['users'] });
            setShowUserRoleDialog(false);
        } catch (error) {
            toast.error('Erreur lors de l\'attribution des rôles');
        }
    };

    const handleAssignPermissions = async () => {
        if (!selectedUser) return;

        try {
            await axios.post(route('user-management.assign-permissions', selectedUser.uuid), {
                permissions: selectedPermissions,
            });
            toast.success('Permissions attribuées avec succès');
            router.reload({ only: ['users'] });
            setShowUserPermissionDialog(false);
        } catch (error) {
            toast.error('Erreur lors de l\'attribution des permissions');
        }
    };

    const handleCreateOrUpdateRole = async () => {
        try {
            if (editingRole && selectedRole) {
                if (!selectedRole.id) {
                    console.error('Selected role missing ID:', selectedRole);
                    toast.error('Erreur: ID du rôle manquant');
                    return;
                }

                // Prevent modification of SuperAdmin role
                if (selectedRole.name === 'SuperAdmin') {
                    console.error('Attempted to modify SuperAdmin role via handleCreateOrUpdateRole');
                    toast.error('Erreur: Le rôle SuperAdmin ne peut pas être modifié');
                    return;
                }

                await axios.put(route('user-management.update-role', selectedRole.id), {
                    name: newRoleName,
                    permissions: selectedPermissions,
                });
                toast.success('Rôle modifié avec succès');
            } else {
                await axios.post(route('user-management.create-role'), {
                    name: newRoleName,
                    permissions: selectedPermissions,
                });
                toast.success('Rôle créé avec succès');
            }
            router.reload({ only: ['roles'] });
            setShowRoleDialog(false);
            setEditingRole(false);
            setNewRoleName('');
            setSelectedPermissions([]);
            setSelectedRole(null);
        } catch (error) {
            toast.error('Erreur lors de l\'opération');
        }
    };

    const handleCreatePermission = async () => {
        // Validation côté frontend
        if (!newPermissionName || newPermissionName.trim().length === 0) {
            toast.error('Le nom de la permission est requis');
            return;
        }

        try {
            await axios.post(route('user-management.create-permission'), {
                name: newPermissionName.trim(),
            });
            toast.success('Permission créée avec succès');
            router.reload({ only: ['permissions'] });
            setShowPermissionDialog(false);
            setNewPermissionName('');
        } catch (error: any) {
            console.error('Permission creation error:', error);
            if (error.response?.status === 422) {
                const validationErrors = error.response.data?.errors;
                if (validationErrors?.name) {
                    toast.error(validationErrors.name[0]);
                } else {
                    toast.error('Erreur de validation');
                }
            } else {
                toast.error('Erreur lors de la création de la permission');
            }
        }
    };

    const openDeleteRoleDialog = (roleId: number) => {
        setRoleToDelete(roleId);
        setShowDeleteRoleDialog(true);
    };

    const handleDeleteRole = async () => {
        if (!roleToDelete) return;

        setIsDeletingRole(true);
        try {
            await axios.delete(route('user-management.delete-role', roleToDelete));
            toast.success('Rôle supprimé avec succès');
            setShowDeleteRoleDialog(false);
            setRoleToDelete(null);
            router.reload({ only: ['roles'] });
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Erreur lors de la suppression du rôle');
        } finally {
            setIsDeletingRole(false);
        }
    };

    const openDeletePermissionDialog = (permissionId: number) => {
        setPermissionToDelete(permissionId);
        setShowDeletePermissionDialog(true);
    };

    const handleDeletePermission = async () => {
        if (!permissionToDelete) return;

        setIsDeletingPermission(true);
        try {
            await axios.delete(route('user-management.delete-permission', permissionToDelete));
            toast.success('Permission supprimée avec succès');
            setShowDeletePermissionDialog(false);
            setPermissionToDelete(null);
            router.reload({ only: ['permissions'] });
        } catch (error) {
            toast.error('Erreur lors de la suppression de la permission');
        } finally {
            setIsDeletingPermission(false);
        }
    };

    const handleToggleRolePermission = async (role: RoleWithPermissions, permissionName: string) => {
        const hasPermission = role.permissions.some(p => p.name === permissionName);
        const newPermissions = hasPermission
            ? role.permissions.filter(p => p.name !== permissionName).map(p => p.name)
            : [...role.permissions.map(p => p.name), permissionName];

        try {
            if (!role.id) {
                console.error('Role missing ID:', role);
                toast.error('Erreur: ID du rôle manquant');
                return;
            }

            // Prevent modification of SuperAdmin role
            if (role.name === 'SuperAdmin') {
                console.error('Attempted to modify SuperAdmin role via handleToggleRolePermission');
                toast.error('Erreur: Le rôle SuperAdmin ne peut pas être modifié');
                return;
            }

            await axios.put(route('user-management.update-role', role.id), {
                name: role.name,
                permissions: newPermissions,
            });
            toast.success('Permission mise à jour avec succès');
            router.reload({ only: ['roles'] });
        } catch (error) {
            toast.error('Erreur lors de la mise à jour de la permission');
        }
    };

    // Group permissions for matrix view
    const matrixPermissionGroups = useMemo(() => {
        const groups: Record<string, string[]> = {};
        permissions.forEach(permission => {
            const parts = permission.name.split(' ');
            const action = parts[0]; // "view", "create", "edit", "delete"
            const model = parts.length > 1 ? parts.slice(1).join(' ') : 'other';

            if (!groups[model]) {
                groups[model] = [];
            }
            if (!groups[model].includes(action)) {
                groups[model].push(action);
            }
        });
        return groups;
    }, [permissions]);

    // Get all available models
    const allModels = useMemo(() => {
        return Object.keys(matrixPermissionGroups).sort();
    }, [matrixPermissionGroups]);

    // Initialize selected models with only "articles" on first render
    React.useEffect(() => {
        if (selectedModels.length === 0 && allModels.length > 0) {
            // Only select "articles" by default
            const articlesModel = allModels.find(model => model.toLowerCase() === 'articles');
            setSelectedModels(articlesModel ? [articlesModel] : []);
        }
    }, [allModels]);

    // Filtered matrix permission groups based on search and selected models
    const filteredMatrixGroups = useMemo(() => {
        const filtered: Record<string, string[]> = {};

        Object.entries(matrixPermissionGroups).forEach(([model, actions]) => {
            // Filter by search
            const matchesSearch = matrixSearch === '' ||
                model.toLowerCase().includes(matrixSearch.toLowerCase());

            // Filter by selected models
            const isSelected = selectedModels.length === 0 || selectedModels.includes(model);

            if (matchesSearch && isSelected) {
                filtered[model] = actions;
            }
        });

        return filtered;
    }, [matrixPermissionGroups, matrixSearch, selectedModels]);

    // Filtered roles for matrix based on search
    const filteredMatrixRoles = useMemo(() => {
        if (!matrixSearch) return roles;

        return roles.filter(role =>
            role.name.toLowerCase().includes(matrixSearch.toLowerCase())
        );
    }, [roles, matrixSearch]);

    const toggleModelSelection = (model: string) => {
        if (selectedModels.includes(model)) {
            setSelectedModels(selectedModels.filter(m => m !== model));
        } else {
            setSelectedModels([...selectedModels, model]);
        }
    };

    const selectAllModels = () => {
        setSelectedModels(allModels);
    };

    const deselectAllModels = () => {
        setSelectedModels([]);
    };

    const openStatusDialog = (userId: string, action: 'activate' | 'deactivate' | 'block' | 'unblock') => {
        setStatusAction({ userId, action });
        setStatusReason('');
        setShowStatusDialog(true);
    };

    const handleToggleUserStatus = async () => {
        if (!statusAction || !statusReason.trim()) {
            toast.error('La raison est obligatoire');
            return;
        }

        if (statusReason.length < 5) {
            toast.error('La raison doit contenir au moins 5 caractères');
            return;
        }

        try {
            await axios.post(route('user-management.toggle-status', statusAction.userId), {
                action: statusAction.action,
                reason: statusReason
            });
            const messages = {
                activate: 'Utilisateur activé avec succès',
                deactivate: 'Utilisateur désactivé avec succès',
                block: 'Utilisateur bloqué avec succès',
                unblock: 'Utilisateur débloqué avec succès',
            };
            toast.success(messages[statusAction.action]);
            setShowStatusDialog(false);
            setStatusAction(null);
            setStatusReason('');
            router.reload({ only: ['users'] });
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Erreur lors de la modification du statut');
        }
    };

    const handleDeleteUser = async () => {
        if (!userToDelete) return;

        setIsDeleting(true);
        try {
            await axios.delete(route('user-management.delete-user', userToDelete));
            toast.success('Utilisateur supprimé avec succès');
            setShowDeleteDialog(false);
            setUserToDelete(null);
            router.reload({ only: ['users'] });
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Erreur lors de la suppression de l\'utilisateur');
        } finally {
            setIsDeleting(false);
        }
    };

    const openDeleteDialog = (userId: string) => {
        setUserToDelete(userId);
        setShowDeleteDialog(true);
    };


    const openUserRoleDialog = (user: UserWithRelations) => {
        setSelectedUser(user);
        setSelectedRoles(user.roles.map(r => r.name));
        setShowUserRoleDialog(true);
    };

    const openUserPermissionDialog = (user: UserWithRelations) => {
        setSelectedUser(user);
        setSelectedPermissions(user.permissions.map(p => p.name));
        setShowUserPermissionDialog(true);
    };

    const openCreateRoleDialog = () => {
        setEditingRole(false);
        setNewRoleName('');
        setSelectedPermissions([]);
        setSelectedRole(null);
        setShowRoleDialog(true);
    };

    const openEditRoleDialog = (role: RoleWithPermissions) => {
        setEditingRole(true);
        setSelectedRole(role);
        setNewRoleName(role.name);
        setSelectedPermissions(role.permissions.map(p => p.name));
        setShowRoleDialog(true);
    };

    const openRoleDetailDialog = (role: RoleWithPermissions) => {
        setSelectedRoleDetail(role);
        setRoleDetailPermissions(role.permissions.map(p => p.name));
        setShowRoleDetailDialog(true);
    };

    const handleSaveRoleDetailPermissions = async () => {
        if (!selectedRoleDetail) {
            toast.error('Erreur: Aucun rôle sélectionné');
            return;
        }

        // Prevent modification of SuperAdmin role
        if (selectedRoleDetail.name === 'SuperAdmin') {
            toast.error('Erreur: Le rôle SuperAdmin ne peut pas être modifié');
            return;
        }

        if (!selectedRoleDetail.id || String(selectedRoleDetail.id) === '' || String(selectedRoleDetail.id) === '0') {
            toast.error('Erreur: ID du rôle manquant ou invalide');
            return;
        }

        try {
            await axios.put(route('user-management.update-role', selectedRoleDetail.id), {
                name: selectedRoleDetail.name,
                permissions: roleDetailPermissions,
            });
            toast.success('Permissions mises à jour avec succès');
            setShowRoleDetailDialog(false);
            setSelectedRoleDetail(null);
            router.reload({ only: ['roles'] });
        } catch (error: any) {
            console.error('ERROR in handleSaveRoleDetailPermissions:', error);
            console.error('Error details:', {
                message: error.message,
                status: error.response?.status,
                statusText: error.response?.statusText,
                data: error.response?.data,
                url: error.config?.url,
                method: error.config?.method
            });
            toast.error(`Erreur lors de la mise à jour des permissions: ${error.response?.status || error.message}`);
        }
    };

    // Get teacher IDs for filtering
    const teacherUserIds = useMemo(() => {
        return new Set(teachers.map(t => t.user_id));
    }, [teachers]);

    // Filtered non-teachers (users who are NOT teachers)
    const filteredNonTeachers = useMemo(() => {
        return users.data.filter(user => {
            const isNotTeacher = !teacherUserIds.has(user.id);
            const matchesSearch =
                user.first_name.toLowerCase().includes(nonTeacherSearch.toLowerCase()) ||
                user.last_name.toLowerCase().includes(nonTeacherSearch.toLowerCase()) ||
                user.email.toLowerCase().includes(nonTeacherSearch.toLowerCase());

            return isNotTeacher && matchesSearch;
        });
    }, [users.data, teacherUserIds, nonTeacherSearch]);

    // Filtered teachers
    const filteredTeachers = useMemo(() => {
        return teachers.filter(teacher => {
            const user = teacher.user;
            const matchesSearch =
                user.first_name.toLowerCase().includes(teacherSearch.toLowerCase()) ||
                user.last_name.toLowerCase().includes(teacherSearch.toLowerCase()) ||
                user.email.toLowerCase().includes(teacherSearch.toLowerCase());

            return matchesSearch;
        });
    }, [teachers, teacherSearch]);

    // Get star user IDs for filtering
    const starUserIds = useMemo(() => {
        return new Set(stars.map(s => s.user_id));
    }, [stars]);

    // Filtered non-stars (users who are NOT stars)
    const filteredNonStars = useMemo(() => {
        return users.data.filter(user => {
            const isNotStar = !starUserIds.has(user.id);
            const matchesSearch =
                user.first_name.toLowerCase().includes(nonStarSearch.toLowerCase()) ||
                user.last_name.toLowerCase().includes(nonStarSearch.toLowerCase()) ||
                user.email.toLowerCase().includes(nonStarSearch.toLowerCase());

            return isNotStar && matchesSearch;
        });
    }, [users.data, starUserIds, nonStarSearch]);

    // Filtered stars
    const filteredStars = useMemo(() => {
        return stars.filter(star => {
            const user = star.user;
            const matchesSearch =
                user.first_name.toLowerCase().includes(starSearch.toLowerCase()) ||
                user.last_name.toLowerCase().includes(starSearch.toLowerCase()) ||
                user.email.toLowerCase().includes(starSearch.toLowerCase());

            return matchesSearch;
        });
    }, [stars, starSearch]);

    // Get employee user IDs for filtering
    const employeeUserIds = useMemo(() => {
        return new Set(employees.map(e => e.user_id));
    }, [employees]);

    // Filtered non-employees (users who are NOT employees)
    const filteredNonEmployees = useMemo(() => {
        return users.data.filter(user => {
            const isNotEmployee = !employeeUserIds.has(user.id);
            const matchesSearch =
                user.first_name.toLowerCase().includes(nonEmployeeSearch.toLowerCase()) ||
                user.last_name.toLowerCase().includes(nonEmployeeSearch.toLowerCase()) ||
                user.email.toLowerCase().includes(nonEmployeeSearch.toLowerCase());

            return isNotEmployee && matchesSearch;
        });
    }, [users.data, employeeUserIds, nonEmployeeSearch]);

    // Filtered employees
    const filteredEmployees = useMemo(() => {
        return employees.filter(employee => {
            const user = employee.user;
            const matchesSearch =
                user.first_name.toLowerCase().includes(employeeSearch.toLowerCase()) ||
                user.last_name.toLowerCase().includes(employeeSearch.toLowerCase()) ||
                user.email.toLowerCase().includes(employeeSearch.toLowerCase());

            return matchesSearch;
        });
    }, [employees, employeeSearch]);

    // Add user as teacher
    const handleAddTeacher = async (user: UserWithRelations) => {
        try {
            await axios.post(route('user-management.add-teacher', user.uuid), {
                is_active: true,
            });
            toast.success(`${user.first_name} ${user.last_name} a été ajouté comme enseignant`);
            router.reload({ only: ['users', 'teachers'] });
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Erreur lors de l\'ajout de l\'enseignant');
        }
    };

    // Remove teacher
    const handleRemoveTeacher = async (teacher: Teacher) => {
        try {
            await axios.delete(route('user-management.remove-teacher', teacher.uuid));
            toast.success(`${teacher.user.first_name} ${teacher.user.last_name} a été retiré des enseignants`);
            router.reload({ only: ['users', 'teachers'] });
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Erreur lors de la suppression de l\'enseignant');
        }
    };

    // Add user as star
    const handleAddStar = async (user: UserWithRelations) => {
        try {
            await axios.post(route('user-management.add-star', user.uuid), {});
            toast.success(`${user.first_name} ${user.last_name} a été ajouté comme Star`);
            router.reload({ only: ['users', 'stars'] });
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Erreur lors de l\'ajout du Star');
        }
    };

    // Remove star
    const handleRemoveStar = async (star: Star) => {
        try {
            await axios.delete(route('user-management.remove-star', star.uuid));
            toast.success(`${star.user.first_name} ${star.user.last_name} a été retiré des Stars`);
            router.reload({ only: ['users', 'stars'] });
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Erreur lors de la suppression du Star');
        }
    };

    // Add user as employee
    const handleAddEmployee = async (user: UserWithRelations) => {
        try {
            await axios.post(route('user-management.add-employee', user.uuid), {});
            toast.success(`${user.first_name} ${user.last_name} a été ajouté comme Employé`);
            router.reload({ only: ['users', 'employees'] });
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Erreur lors de l\'ajout de l\'Employé');
        }
    };

    // Remove employee
    const handleRemoveEmployee = async (employee: Employee) => {
        try {
            await axios.delete(route('user-management.remove-employee', employee.uuid));
            toast.success(`${employee.user.first_name} ${employee.user.last_name} a été retiré des Employés`);
            router.reload({ only: ['users', 'employees'] });
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Erreur lors de la suppression de l\'Employé');
        }
    };

    return (
        <DashboardLayout
            title="Gestion des Utilisateurs"
            description="Gérez les utilisateurs, rôles et permissions de l'organisation"
        >
            <Head title="Gestion des Utilisateurs" />

            <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div className="p-6">
                    {/* Tabs */}
                    <div className="border-b border-gray-200 dark:border-gray-700 mb-6">
                        <nav className="-mb-px flex space-x-8">
                            <button
                                onClick={() => setActiveTab('users')}
                                className={`${activeTab === 'users'
                                    ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                            >
                                Utilisateurs ({users.data.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('roles')}
                                className={`${activeTab === 'roles'
                                    ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                            >
                                Rôles ({roles.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('permissions')}
                                className={`${activeTab === 'permissions'
                                    ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                            >
                                Permissions ({permissions.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('matrix')}
                                className={`${activeTab === 'matrix'
                                    ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                            >
                                Matrice des Permissions
                            </button>
                            <button
                                onClick={() => setActiveTab('teachers')}
                                className={`${activeTab === 'teachers'
                                    ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                            >
                                Enseignants ({teachers.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('stars')}
                                className={`${activeTab === 'stars'
                                    ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                            >
                                Stars ({stars.length})
                            </button>
                            <button
                                onClick={() => setActiveTab('employees')}
                                className={`${activeTab === 'employees'
                                    ? 'border-violet-500 text-violet-600 dark:text-violet-400'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                    } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                            >
                                Employés ({employees.length})
                            </button>
                        </nav>
                    </div>

                    {/* Users Tab */}
                    {activeTab === 'users' && (
                        <div>
                            {/* Search, Filter and View Toggle */}
                            <div className="mb-4 flex gap-4 flex-wrap">
                                <div className="relative flex-1 min-w-[200px]">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Rechercher un utilisateur..."
                                        value={userSearch}
                                        onChange={(e) => handleSearchChange(e.target.value)}
                                        className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        disabled={isLoadingUsers}
                                    />
                                    {isLoadingUsers && (
                                        <div className="absolute right-3 top-1/2 -translate-y-1/2">
                                            <div className="animate-spin h-4 w-4 border-2 border-purple-500 border-t-transparent rounded-full"></div>
                                        </div>
                                    )}
                                </div>
                                <select
                                    value={roleFilter}
                                    onChange={(e) => handleRoleFilterChange(e.target.value)}
                                    className="px-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    disabled={isLoadingUsers}
                                >
                                    <option value="">Tous les rôles</option>
                                    {roles.map(role => (
                                        <option key={role.id} value={role.name}>{role.name}</option>
                                    ))}
                                </select>
                                <div className="flex gap-1 border rounded-lg p-1 dark:border-gray-600">
                                    <button
                                        onClick={() => setUserViewMode('table')}
                                        className={`p-2 rounded transition-colors ${userViewMode === 'table'
                                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300'
                                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
                                            }`}
                                        title="Vue tableau"
                                    >
                                        <TableCellsIcon className="h-5 w-5" />
                                    </button>
                                    <button
                                        onClick={() => setUserViewMode('list')}
                                        className={`p-2 rounded transition-colors ${userViewMode === 'list'
                                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300'
                                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
                                            }`}
                                        title="Vue liste"
                                    >
                                        <ListBulletIcon className="h-5 w-5" />
                                    </button>
                                    <button
                                        onClick={() => setUserViewMode('grid')}
                                        className={`p-2 rounded transition-colors ${userViewMode === 'grid'
                                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300'
                                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
                                            }`}
                                        title="Vue grille"
                                    >
                                        <Squares2X2Icon className="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            {/* Table View */}
                            {userViewMode === 'table' && (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-900">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Utilisateur</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Rôles</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            {filteredUsers.map((user) => {
                                                const isSuperAdmin = user.roles.some(r => r.name === 'SuperAdmin');

                                                return (
                                                    <tr key={user.id} className={user.is_blocked ? 'bg-red-50 dark:bg-red-900/10' : ''}>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <a
                                                                href={route('user-management.show', user.uuid)}
                                                                className="text-sm font-medium text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300 hover:underline"
                                                            >
                                                                {user.first_name} {user.last_name}
                                                            </a>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                            {user.email}
                                                        </td>
                                                        <td className="px-6 py-4">
                                                            <div className="flex flex-wrap gap-1">
                                                                {user.roles.map((role) => (
                                                                    <span key={role.id} className="px-2 py-1 text-xs font-medium rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200">
                                                                        {role.name}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            {user.is_blocked ? (
                                                                <span className="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                                    Bloqué
                                                                </span>
                                                            ) : user.is_active === false ? (
                                                                <span className="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                                    Inactif
                                                                </span>
                                                            ) : (
                                                                <span className="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                                    Actif
                                                                </span>
                                                            )}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                            <div className="flex justify-end gap-2">
                                                                <button
                                                                    onClick={() => openUserRoleDialog(user)}
                                                                    className="text-violet-600 hover:text-violet-900 dark:text-violet-400"
                                                                    title="Gérer les rôles"
                                                                >
                                                                    <UserPlusIcon className="h-5 w-5" />
                                                                </button>
                                                                <button
                                                                    onClick={() => openUserPermissionDialog(user)}
                                                                    className="text-primary hover:text-blue-900 dark:text-blue-400"
                                                                    title="Gérer les permissions"
                                                                >
                                                                    <ShieldCheckIcon className="h-5 w-5" />
                                                                </button>

                                                                {!isSuperAdmin && (
                                                                    <>
                                                                        {user.is_blocked ? (
                                                                            <button
                                                                                onClick={() => openStatusDialog(user.uuid, 'unblock')}
                                                                                className="text-green-600 hover:text-green-900 dark:text-green-400"
                                                                                title="Débloquer l'utilisateur"
                                                                            >
                                                                                <CheckIcon className="h-5 w-5" />
                                                                            </button>
                                                                        ) : (
                                                                            <button
                                                                                onClick={() => openStatusDialog(user.uuid, 'block')}
                                                                                className="text-orange-600 hover:text-orange-900 dark:text-orange-400"
                                                                                title="Bloquer l'utilisateur"
                                                                            >
                                                                                <NoSymbolIcon className="h-5 w-5" />
                                                                            </button>
                                                                        )}

                                                                        {user.is_active ? (
                                                                            <button
                                                                                onClick={() => openStatusDialog(user.uuid, 'deactivate')}
                                                                                className="text-gray-600 hover:text-gray-900 dark:text-gray-400"
                                                                                title="Désactiver l'utilisateur"
                                                                            >
                                                                                <LockClosedIcon className="h-5 w-5" />
                                                                            </button>
                                                                        ) : (
                                                                            <button
                                                                                onClick={() => openStatusDialog(user.uuid, 'activate')}
                                                                                className="text-green-600 hover:text-green-900 dark:text-green-400"
                                                                                title="Activer l'utilisateur"
                                                                            >
                                                                                <CheckIcon className="h-5 w-5" />
                                                                            </button>
                                                                        )}

                                                                        <button
                                                                            onClick={() => openDeleteDialog(user.uuid)}
                                                                            className="text-red-600 hover:text-red-900 dark:text-red-400"
                                                                            title="Supprimer l'utilisateur"
                                                                        >
                                                                            <TrashIcon className="h-5 w-5" />
                                                                        </button>
                                                                    </>
                                                                )}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* List View */}
                            {userViewMode === 'list' && (
                                <div className="space-y-3">
                                    {filteredUsers.map((user) => {
                                        const isSuperAdmin = user.roles.some(r => r.name === 'SuperAdmin');
                                        return (
                                            <div
                                                key={user.id}
                                                className={`p-4 border rounded-lg dark:border-gray-700 hover:shadow-md transition-shadow ${user.is_blocked ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800' : ''
                                                    }`}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-3 mb-2">
                                                            <a
                                                                href={route('user-management.show', user.uuid)}
                                                                className="text-lg font-semibold text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300 hover:underline"
                                                            >
                                                                {user.first_name} {user.last_name}
                                                            </a>
                                                            {user.is_blocked ? (
                                                                <span className="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                                    Bloqué
                                                                </span>
                                                            ) : user.is_active === false ? (
                                                                <span className="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                                    Inactif
                                                                </span>
                                                            ) : (
                                                                <span className="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                                    Actif
                                                                </span>
                                                            )}
                                                        </div>
                                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">{user.email}</p>
                                                        <div className="flex flex-wrap gap-1">
                                                            {user.roles.map((role) => (
                                                                <span key={role.id} className="px-2 py-1 text-xs font-medium rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200">
                                                                    {role.name}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    </div>
                                                    <div className="flex gap-2">
                                                        <button
                                                            onClick={() => openUserRoleDialog(user)}
                                                            className="text-violet-600 hover:text-violet-900 dark:text-violet-400"
                                                            title="Gérer les rôles"
                                                        >
                                                            <UserPlusIcon className="h-5 w-5" />
                                                        </button>
                                                        <button
                                                            onClick={() => openUserPermissionDialog(user)}
                                                            className="text-primary hover:text-blue-900 dark:text-blue-400"
                                                            title="Gérer les permissions"
                                                        >
                                                            <ShieldCheckIcon className="h-5 w-5" />
                                                        </button>
                                                        {!isSuperAdmin && (
                                                            <>
                                                                {user.is_blocked ? (
                                                                    <button
                                                                        onClick={() => openStatusDialog(user.uuid, 'unblock')}
                                                                        className="text-green-600 hover:text-green-900 dark:text-green-400"
                                                                        title="Débloquer l'utilisateur"
                                                                    >
                                                                        <CheckIcon className="h-5 w-5" />
                                                                    </button>
                                                                ) : (
                                                                    <button
                                                                        onClick={() => openStatusDialog(user.uuid, 'block')}
                                                                        className="text-orange-600 hover:text-orange-900 dark:text-orange-400"
                                                                        title="Bloquer l'utilisateur"
                                                                    >
                                                                        <NoSymbolIcon className="h-5 w-5" />
                                                                    </button>
                                                                )}
                                                                {user.is_active ? (
                                                                    <button
                                                                        onClick={() => openStatusDialog(user.uuid, 'deactivate')}
                                                                        className="text-gray-600 hover:text-gray-900 dark:text-gray-400"
                                                                        title="Désactiver l'utilisateur"
                                                                    >
                                                                        <LockClosedIcon className="h-5 w-5" />
                                                                    </button>
                                                                ) : (
                                                                    <button
                                                                        onClick={() => openStatusDialog(user.uuid, 'activate')}
                                                                        className="text-green-600 hover:text-green-900 dark:text-green-400"
                                                                        title="Activer l'utilisateur"
                                                                    >
                                                                        <CheckIcon className="h-5 w-5" />
                                                                    </button>
                                                                )}
                                                                <button
                                                                    onClick={() => openDeleteDialog(user.uuid)}
                                                                    className="text-red-600 hover:text-red-900 dark:text-red-400"
                                                                    title="Supprimer l'utilisateur"
                                                                >
                                                                    <TrashIcon className="h-5 w-5" />
                                                                </button>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            {/* Grid View */}
                            {userViewMode === 'grid' && (
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {filteredUsers.map((user) => {
                                        const isSuperAdmin = user.roles.some(r => r.name === 'SuperAdmin');
                                        return (
                                            <div
                                                key={user.id}
                                                className={`p-4 border rounded-lg dark:border-gray-700 hover:shadow-md transition-shadow ${user.is_blocked ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800' : ''
                                                    }`}
                                            >
                                                <div className="flex items-start justify-between mb-3">
                                                    <div className="flex items-center gap-2">
                                                        <div className="h-10 w-10 rounded-full bg-violet-100 dark:bg-violet-900 flex items-center justify-center">
                                                            <UserIcon className="h-6 w-6 text-violet-600 dark:text-violet-300" />
                                                        </div>
                                                        {user.is_blocked ? (
                                                            <span className="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                                Bloqué
                                                            </span>
                                                        ) : user.is_active === false ? (
                                                            <span className="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                                Inactif
                                                            </span>
                                                        ) : (
                                                            <span className="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                                Actif
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                                <a
                                                    href={route('user-management.show', user.uuid)}
                                                    className="block text-lg font-semibold text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300 hover:underline mb-1"
                                                >
                                                    {user.first_name} {user.last_name}
                                                </a>
                                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">{user.email}</p>
                                                <div className="mb-3">
                                                    <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Rôles:</p>
                                                    <div className="flex flex-wrap gap-1">
                                                        {user.roles.map((role) => (
                                                            <span key={role.id} className="px-2 py-1 text-xs font-medium rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200">
                                                                {role.name}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                                <div className="flex gap-2 pt-3 border-t dark:border-gray-700">
                                                    <button
                                                        onClick={() => openUserRoleDialog(user)}
                                                        className="text-violet-600 hover:text-violet-900 dark:text-violet-400"
                                                        title="Gérer les rôles"
                                                    >
                                                        <UserPlusIcon className="h-5 w-5" />
                                                    </button>
                                                    <button
                                                        onClick={() => openUserPermissionDialog(user)}
                                                        className="text-primary hover:text-blue-900 dark:text-blue-400"
                                                        title="Gérer les permissions"
                                                    >
                                                        <ShieldCheckIcon className="h-5 w-5" />
                                                    </button>
                                                    {!isSuperAdmin && (
                                                        <>
                                                            {user.is_blocked ? (
                                                                <button
                                                                    onClick={() => openStatusDialog(user.uuid, 'unblock')}
                                                                    className="text-green-600 hover:text-green-900 dark:text-green-400"
                                                                    title="Débloquer l'utilisateur"
                                                                >
                                                                    <CheckIcon className="h-5 w-5" />
                                                                </button>
                                                            ) : (
                                                                <button
                                                                    onClick={() => openStatusDialog(user.uuid, 'block')}
                                                                    className="text-orange-600 hover:text-orange-900 dark:text-orange-400"
                                                                    title="Bloquer l'utilisateur"
                                                                >
                                                                    <NoSymbolIcon className="h-5 w-5" />
                                                                </button>
                                                            )}
                                                            {user.is_active ? (
                                                                <button
                                                                    onClick={() => openStatusDialog(user.uuid, 'deactivate')}
                                                                    className="text-gray-600 hover:text-gray-900 dark:text-gray-400"
                                                                    title="Désactiver l'utilisateur"
                                                                >
                                                                    <LockClosedIcon className="h-5 w-5" />
                                                                </button>
                                                            ) : (
                                                                <button
                                                                    onClick={() => openStatusDialog(user.uuid, 'activate')}
                                                                    className="text-green-600 hover:text-green-900 dark:text-green-400"
                                                                    title="Activer l'utilisateur"
                                                                >
                                                                    <CheckIcon className="h-5 w-5" />
                                                                </button>
                                                            )}
                                                            <button
                                                                onClick={() => openDeleteDialog(user.uuid)}
                                                                className="text-red-600 hover:text-red-900 dark:text-red-400"
                                                                title="Supprimer l'utilisateur"
                                                            >
                                                                <TrashIcon className="h-5 w-5" />
                                                            </button>
                                                        </>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}

                            {/* Pagination */}
                            {users.last_page > 1 && (
                                <div className="mt-6 border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <Pagination
                                        currentPage={users.current_page}
                                        lastPage={users.last_page}
                                        total={users.total}
                                        perPage={users.per_page}
                                        from={users.from}
                                        to={users.to}
                                        onPageChange={handlePageChange}
                                        showPageSizeSelector={true}
                                        pageSizes={[10, 20, 50, 100]}
                                        pageSize={filters?.per_page ?? 20}
                                        onPageSizeChange={handlePageSizeChange}
                                        isLoading={isLoadingUsers}
                                    />
                                </div>
                            )}

                            {/* Summary when only one page */}
                            {users.last_page === 1 && users.total > 0 && (
                                <div className="mt-4 text-sm text-gray-600 dark:text-gray-400">
                                    {users.total} utilisateur{users.total > 1 ? 's' : ''} au total
                                </div>
                            )}
                        </div>
                    )}

                    {/* Roles Tab */}
                    {activeTab === 'roles' && (
                        <div>
                            {/* Search, View Toggle and Create */}
                            <div className="mb-4 flex gap-4 items-center">
                                <div className="relative flex-1">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Rechercher un rôle..."
                                        value={roleSearch}
                                        onChange={(e) => setRoleSearch(e.target.value)}
                                        className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    />
                                </div>
                                <div className="flex gap-1 border rounded-lg p-1 dark:border-gray-600">
                                    <button
                                        onClick={() => setRoleViewMode('grid')}
                                        className={`p-2 rounded transition-colors ${roleViewMode === 'grid'
                                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300'
                                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
                                            }`}
                                        title="Vue grille"
                                    >
                                        <Squares2X2Icon className="h-5 w-5" />
                                    </button>
                                    <button
                                        onClick={() => setRoleViewMode('list')}
                                        className={`p-2 rounded transition-colors ${roleViewMode === 'list'
                                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-900 dark:text-violet-300'
                                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700'
                                            }`}
                                        title="Vue liste"
                                    >
                                        <ListBulletIcon className="h-5 w-5" />
                                    </button>
                                </div>
                                <Button onClick={openCreateRoleDialog}>
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Nouveau Rôle
                                </Button>
                            </div>

                            {/* Grid View */}
                            {roleViewMode === 'grid' && (
                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {filteredRoles.map((role) => (
                                        <div key={role.id} className="p-4 border rounded-lg dark:border-gray-700 hover:shadow-md transition-shadow">
                                            <div className="flex justify-between items-start mb-2">
                                                <button
                                                    onClick={() => openRoleDetailDialog(role)}
                                                    className="font-semibold text-lg text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300 hover:underline text-left"
                                                >
                                                    {role.name}
                                                </button>
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={() => openEditRoleDialog(role)}
                                                        className="text-primary hover:text-blue-900 dark:text-blue-400"
                                                        title="Éditer"
                                                    >
                                                        <PencilIcon className="h-4 w-4" />
                                                    </button>
                                                    {role.name !== 'SuperAdmin' && (
                                                        <button
                                                            onClick={() => openDeleteRoleDialog(role.id)}
                                                            className="text-red-600 hover:text-red-900 dark:text-red-400"
                                                            title="Supprimer"
                                                        >
                                                            <TrashIcon className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                {role.permissions.length} permissions
                                            </div>
                                            <div className="flex flex-wrap gap-1">
                                                {role.permissions.slice(0, 5).map((permission) => (
                                                    <span key={permission.id} className="px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                        {permission.name}
                                                    </span>
                                                ))}
                                                {role.permissions.length > 5 && (
                                                    <span className="px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                        +{role.permissions.length - 5}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* List View */}
                            {roleViewMode === 'list' && (
                                <div className="space-y-2">
                                    {filteredRoles.map((role) => (
                                        <div key={role.id} className="p-4 border rounded-lg dark:border-gray-700 hover:shadow-md transition-shadow">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-4 flex-1">
                                                    <button
                                                        onClick={() => openRoleDetailDialog(role)}
                                                        className="font-semibold text-lg text-violet-600 hover:text-violet-900 dark:text-violet-400 dark:hover:text-violet-300 hover:underline"
                                                    >
                                                        {role.name}
                                                    </button>
                                                    <span className="text-sm text-gray-600 dark:text-gray-400">
                                                        {role.permissions.length} permissions
                                                    </span>
                                                </div>
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={() => openEditRoleDialog(role)}
                                                        className="text-primary hover:text-blue-900 dark:text-blue-400"
                                                        title="Éditer"
                                                    >
                                                        <PencilIcon className="h-4 w-4" />
                                                    </button>
                                                    {role.name !== 'SuperAdmin' && (
                                                        <button
                                                            onClick={() => openDeleteRoleDialog(role.id)}
                                                            className="text-red-600 hover:text-red-900 dark:text-red-400"
                                                            title="Supprimer"
                                                        >
                                                            <TrashIcon className="h-4 w-4" />
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex flex-wrap gap-1 mt-3">
                                                {role.permissions.map((permission) => (
                                                    <span key={permission.id} className="px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                        {permission.name}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Permissions Tab */}
                    {activeTab === 'permissions' && (
                        <div>
                            {/* Search and Create */}
                            <div className="mb-4 flex gap-4 items-center">
                                <div className="relative flex-1">
                                    <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Rechercher une permission..."
                                        value={permissionSearch}
                                        onChange={(e) => setPermissionSearch(e.target.value)}
                                        className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    />
                                </div>
                                <Button onClick={() => setShowPermissionDialog(true)}>
                                    <PlusIcon className="h-4 w-4 mr-2" />
                                    Nouvelle Permission
                                </Button>
                            </div>

                            {/* Grouped Permissions */}
                            <div className="space-y-6">
                                {Object.entries(filteredGroupedPermissions).sort().map(([model, perms]) => (
                                    <div key={model}>
                                        <h3 className="text-lg font-semibold mb-3 text-gray-900 dark:text-white border-b pb-2">
                                            {model} ({perms.length})
                                        </h3>
                                        <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                                            {perms.map((permission) => (
                                                <div key={permission.id} className="flex justify-between items-center p-3 border rounded dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                    <span className="text-sm text-gray-900 dark:text-white">{permission.name}</span>
                                                    <button
                                                        onClick={() => openDeletePermissionDialog(permission.id)}
                                                        className="text-red-600 hover:text-red-900 dark:text-red-400"
                                                        title="Supprimer"
                                                    >
                                                        <TrashIcon className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Matrix Tab */}
                    {activeTab === 'matrix' && (
                        <div>
                            {/* Search and Filters */}
                            <div className="mb-6 space-y-4">
                                <div className="flex gap-4 items-center">
                                    <div className="relative flex-1">
                                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                        <input
                                            type="text"
                                            placeholder="Rechercher un rôle ou un modèle..."
                                            value={matrixSearch}
                                            onChange={(e) => setMatrixSearch(e.target.value)}
                                            className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        />
                                    </div>
                                    {matrixSearch && (
                                        <button
                                            onClick={() => setMatrixSearch('')}
                                            className="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                            title="Effacer la recherche"
                                        >
                                            <XMarkIcon className="h-5 w-5" />
                                        </button>
                                    )}
                                </div>

                                {/* Model Selection */}
                                <div className="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div className="flex items-center justify-between mb-3">
                                        <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                                            Modèles à afficher ({selectedModels.length}/{allModels.length})
                                        </h3>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={selectAllModels}
                                                className="text-xs px-3 py-1 bg-violet-100 hover:bg-violet-200 dark:bg-violet-900/40 dark:hover:bg-violet-900/60 text-violet-700 dark:text-violet-300 rounded transition-colors"
                                            >
                                                Tout sélectionner
                                            </button>
                                            <button
                                                onClick={deselectAllModels}
                                                className="text-xs px-3 py-1 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded transition-colors"
                                            >
                                                Tout désélectionner
                                            </button>
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
                                        {allModels.map(model => (
                                            <label
                                                key={model}
                                                className="flex items-center space-x-2 p-2 hover:bg-white dark:hover:bg-gray-800 rounded cursor-pointer transition-colors"
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={selectedModels.includes(model)}
                                                    onChange={() => toggleModelSelection(model)}
                                                    className="rounded text-violet-600 focus:ring-violet-500"
                                                />
                                                <span className="text-sm text-gray-900 dark:text-white">
                                                    {model.charAt(0).toUpperCase() + model.slice(1)}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Cliquez sur les cellules pour activer ou désactiver les permissions pour chaque rôle.
                                </p>
                            </div>

                            {/* Matrix Table */}
                            {Object.keys(filteredMatrixGroups).length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full border-collapse">
                                        <thead>
                                            <tr>
                                                <th className="sticky left-0 z-20 bg-gray-50 dark:bg-gray-900 px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white border-b-2 border-r-2 border-gray-300 dark:border-gray-600">
                                                    Rôle / Permission
                                                </th>
                                                {Object.entries(filteredMatrixGroups).sort().map(([model, actions]) => (
                                                    <th key={model} colSpan={actions.length} className="px-4 py-3 text-center text-sm font-semibold text-gray-900 dark:text-white border-b-2 border-gray-300 dark:border-gray-600 bg-violet-50 dark:bg-violet-900/20">
                                                        {model.charAt(0).toUpperCase() + model.slice(1)}
                                                    </th>
                                                ))}
                                            </tr>
                                            <tr>
                                                <th className="sticky left-0 z-20 bg-gray-50 dark:bg-gray-900 border-r-2 border-b border-gray-300 dark:border-gray-600"></th>
                                                {Object.entries(filteredMatrixGroups).sort().map(([model, actions]) =>
                                                    actions.map(action => (
                                                        <th key={`${model}-${action}`} className="px-2 py-2 text-center text-xs font-medium text-gray-700 dark:text-gray-300 border-b border-l border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                                                            {action}
                                                        </th>
                                                    ))
                                                )}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {filteredMatrixRoles.map((role, roleIndex) => (
                                                <tr key={role.id} className={roleIndex % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-800/50'}>
                                                    <td className="sticky left-0 z-10 px-4 py-3 text-sm font-medium text-gray-900 dark:text-white border-r-2 border-gray-300 dark:border-gray-600 bg-inherit">
                                                        {role.name}
                                                    </td>
                                                    {Object.entries(filteredMatrixGroups).sort().map(([model, actions]) =>
                                                        actions.map(action => {
                                                            const permissionName = `${action} ${model}`;
                                                            const hasPermission = role.permissions.some(p => p.name === permissionName);
                                                            return (
                                                                <td key={`${role.id}-${model}-${action}`} className="border-l border-b border-gray-200 dark:border-gray-700 p-0">
                                                                    <button
                                                                        onClick={() => handleToggleRolePermission(role, permissionName)}
                                                                        disabled={role.name === 'SuperAdmin'}
                                                                        className={`w-full h-full px-2 py-3 flex items-center justify-center transition-colors ${hasPermission
                                                                            ? 'bg-green-100 hover:bg-green-200 dark:bg-green-900/40 dark:hover:bg-green-900/60'
                                                                            : 'bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30'
                                                                            } ${role.name === 'SuperAdmin' ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'}`}
                                                                        title={hasPermission ? `Désactiver ${permissionName}` : `Activer ${permissionName}`}
                                                                    >
                                                                        {hasPermission ? (
                                                                            <CheckIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                                                                        ) : (
                                                                            <XCircleIcon className="h-5 w-5 text-red-400 dark:text-red-600" />
                                                                        )}
                                                                    </button>
                                                                </td>
                                                            );
                                                        })
                                                    )}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <p>Aucun modèle sélectionné ou correspondant à votre recherche.</p>
                                    <p className="text-sm mt-2">Sélectionnez au moins un modèle pour afficher la matrice.</p>
                                </div>
                            )}

                            <div className="mt-4 flex items-center gap-6 text-sm">
                                <div className="flex items-center gap-2">
                                    <CheckIcon className="h-5 w-5 text-green-600 dark:text-green-400" />
                                    <span className="text-gray-600 dark:text-gray-400">Permission active</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <XCircleIcon className="h-5 w-5 text-red-400 dark:text-red-600" />
                                    <span className="text-gray-600 dark:text-gray-400">Permission inactive</span>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Teachers Tab */}
                    {activeTab === 'teachers' && (
                        <div>
                            <div className="mb-6">
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Gérez les enseignants de votre organisation. Vous pouvez ajouter des utilisateurs comme enseignants ou les retirer.
                                </p>
                            </div>

                            <div className="grid md:grid-cols-2 gap-6">
                                {/* Left Column - Non-Teachers (Users to Add) */}
                                <div className="border rounded-lg dark:border-gray-700 overflow-hidden">
                                    <div className="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b dark:border-gray-700">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3">
                                            Utilisateurs disponibles ({filteredNonTeachers.length})
                                        </h3>
                                        <div className="relative">
                                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                            <input
                                                type="text"
                                                placeholder="Rechercher un utilisateur..."
                                                value={nonTeacherSearch}
                                                onChange={(e) => setNonTeacherSearch(e.target.value)}
                                                className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                            />
                                        </div>
                                    </div>
                                    <div className="p-4 max-h-[600px] overflow-y-auto">
                                        {filteredNonTeachers.length === 0 ? (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                <UserIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                                <p className="text-sm">Aucun utilisateur disponible</p>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                {filteredNonTeachers.map((user) => (
                                                    <div
                                                        key={user.id}
                                                        className="flex items-center justify-between p-3 border rounded-lg dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                                    >
                                                        <div className="flex-1 min-w-0">
                                                            <p className="font-medium text-gray-900 dark:text-white truncate">
                                                                {user.first_name} {user.last_name}
                                                            </p>
                                                            <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                                {user.email}
                                                            </p>
                                                            <div className="flex flex-wrap gap-1 mt-1">
                                                                {user.roles.map((role) => (
                                                                    <span key={role.id} className="px-2 py-0.5 text-xs rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200">
                                                                        {role.name}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        </div>
                                                        <Button
                                                            onClick={() => handleAddTeacher(user)}
                                                            size="sm"
                                                            className="ml-3 flex-shrink-0"
                                                            title="Ajouter comme enseignant"
                                                        >
                                                            <ArrowRightIcon className="h-5 w-5" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Right Column - Teachers (Can Remove) */}
                                <div className="border rounded-lg dark:border-gray-700 overflow-hidden">
                                    <div className="bg-violet-50 dark:bg-violet-900/20 px-4 py-3 border-b dark:border-gray-700">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3">
                                            Enseignants actuels ({filteredTeachers.length})
                                        </h3>
                                        <div className="relative">
                                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                            <input
                                                type="text"
                                                placeholder="Rechercher un enseignant..."
                                                value={teacherSearch}
                                                onChange={(e) => setTeacherSearch(e.target.value)}
                                                className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                            />
                                        </div>
                                    </div>
                                    <div className="p-4 max-h-[600px] overflow-y-auto">
                                        {filteredTeachers.length === 0 ? (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                <UserIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                                <p className="text-sm">Aucun enseignant</p>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                {filteredTeachers.map((teacher) => (
                                                    <div
                                                        key={teacher.uuid}
                                                        className="p-3 border rounded-lg dark:border-gray-700 hover:bg-violet-50/50 dark:hover:bg-violet-900/10 transition-colors"
                                                    >
                                                        <div className="flex items-start justify-between mb-2">
                                                            <div className="flex-1 min-w-0">
                                                                <p className="font-medium text-gray-900 dark:text-white truncate">
                                                                    {teacher.user.first_name} {teacher.user.last_name}
                                                                </p>
                                                                <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                                    {teacher.user.email}
                                                                </p>
                                                            </div>
                                                            <button
                                                                onClick={() => handleRemoveTeacher(teacher)}
                                                                className="ml-3 text-red-600 hover:text-red-900 dark:text-red-400 flex-shrink-0"
                                                                title="Retirer l'enseignant"
                                                            >
                                                                <TrashIcon className="h-5 w-5" />
                                                            </button>
                                                        </div>
                                                        {(teacher.specialization || teacher.experience_years || teacher.phone) && (
                                                            <div className="mt-2 pt-2 border-t dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                                                {teacher.specialization && (
                                                                    <p>
                                                                        <span className="font-medium">Spécialisation:</span> {teacher.specialization}
                                                                    </p>
                                                                )}
                                                                {teacher.experience_years !== null && teacher.experience_years > 0 && (
                                                                    <p>
                                                                        <span className="font-medium">Expérience:</span> {teacher.experience_years} an{teacher.experience_years > 1 ? 's' : ''}
                                                                    </p>
                                                                )}
                                                                {teacher.phone && (
                                                                    <p>
                                                                        <span className="font-medium">Téléphone:</span> {teacher.phone}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Stars Tab */}
                    {activeTab === 'stars' && (
                        <div>
                            <div className="mb-6">
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Gérez les Stars (bénévoles) de votre organisation. Vous pouvez ajouter des utilisateurs comme Stars ou les retirer.
                                </p>
                            </div>

                            <div className="grid md:grid-cols-2 gap-6">
                                {/* Left Column - Non-Stars (Users to Add) */}
                                <div className="border rounded-lg dark:border-gray-700 overflow-hidden">
                                    <div className="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b dark:border-gray-700">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3">
                                            Utilisateurs disponibles ({filteredNonStars.length})
                                        </h3>
                                        <div className="relative">
                                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                            <input
                                                type="text"
                                                placeholder="Rechercher un utilisateur..."
                                                value={nonStarSearch}
                                                onChange={(e) => setNonStarSearch(e.target.value)}
                                                className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                            />
                                        </div>
                                    </div>
                                    <div className="p-4 max-h-[600px] overflow-y-auto">
                                        {filteredNonStars.length === 0 ? (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                <UserIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                                <p className="text-sm">Aucun utilisateur disponible</p>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                {filteredNonStars.map((user) => (
                                                    <div
                                                        key={user.id}
                                                        className="flex items-center justify-between p-3 border rounded-lg dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                                    >
                                                        <div className="flex-1 min-w-0">
                                                            <p className="font-medium text-gray-900 dark:text-white truncate">
                                                                {user.first_name} {user.last_name}
                                                            </p>
                                                            <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                                {user.email}
                                                            </p>
                                                            <div className="flex flex-wrap gap-1 mt-1">
                                                                {user.roles.map((role) => (
                                                                    <span key={role.id} className="px-2 py-0.5 text-xs rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200">
                                                                        {role.name}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        </div>
                                                        <Button
                                                            onClick={() => handleAddStar(user)}
                                                            size="sm"
                                                            className="ml-3 flex-shrink-0"
                                                            title="Ajouter comme Star"
                                                        >
                                                            <ArrowRightIcon className="h-5 w-5" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Right Column - Stars (Can Remove) */}
                                <div className="border rounded-lg dark:border-gray-700 overflow-hidden">
                                    <div className="bg-amber-50 dark:bg-amber-900/20 px-4 py-3 border-b dark:border-gray-700">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3">
                                            Stars actuels ({filteredStars.length})
                                        </h3>
                                        <div className="relative">
                                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                            <input
                                                type="text"
                                                placeholder="Rechercher un Star..."
                                                value={starSearch}
                                                onChange={(e) => setStarSearch(e.target.value)}
                                                className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                            />
                                        </div>
                                    </div>
                                    <div className="p-4 max-h-[600px] overflow-y-auto">
                                        {filteredStars.length === 0 ? (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                <UserIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                                <p className="text-sm">Aucun Star</p>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                {filteredStars.map((star) => (
                                                    <div
                                                        key={star.uuid}
                                                        className="p-3 border rounded-lg dark:border-gray-700 hover:bg-amber-50/50 dark:hover:bg-amber-900/10 transition-colors"
                                                    >
                                                        <div className="flex items-start justify-between mb-2">
                                                            <div className="flex-1 min-w-0">
                                                                <p className="font-medium text-gray-900 dark:text-white truncate">
                                                                    {star.user.first_name} {star.user.last_name}
                                                                </p>
                                                                <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                                    {star.user.email}
                                                                </p>
                                                            </div>
                                                            <button
                                                                onClick={() => handleRemoveStar(star)}
                                                                className="ml-3 text-red-600 hover:text-red-900 dark:text-red-400 flex-shrink-0"
                                                                title="Retirer le Star"
                                                            >
                                                                <TrashIcon className="h-5 w-5" />
                                                            </button>
                                                        </div>
                                                        {(star.title || star.type || star.level) && (
                                                            <div className="mt-2 pt-2 border-t dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                                                {star.title && (
                                                                    <p>
                                                                        <span className="font-medium">Titre:</span> {star.title}
                                                                    </p>
                                                                )}
                                                                {star.type && (
                                                                    <p>
                                                                        <span className="font-medium">Type:</span> {star.type}
                                                                    </p>
                                                                )}
                                                                {star.level && (
                                                                    <p>
                                                                        <span className="font-medium">Niveau:</span> {star.level}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Employees Tab */}
                    {activeTab === 'employees' && (
                        <div>
                            <div className="mb-6">
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Gérez les Employés de votre organisation. Vous pouvez ajouter des utilisateurs comme Employés ou les retirer.
                                </p>
                            </div>

                            <div className="grid md:grid-cols-2 gap-6">
                                {/* Left Column - Non-Employees (Users to Add) */}
                                <div className="border rounded-lg dark:border-gray-700 overflow-hidden">
                                    <div className="bg-gray-50 dark:bg-gray-900 px-4 py-3 border-b dark:border-gray-700">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3">
                                            Utilisateurs disponibles ({filteredNonEmployees.length})
                                        </h3>
                                        <div className="relative">
                                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                            <input
                                                type="text"
                                                placeholder="Rechercher un utilisateur..."
                                                value={nonEmployeeSearch}
                                                onChange={(e) => setNonEmployeeSearch(e.target.value)}
                                                className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                            />
                                        </div>
                                    </div>
                                    <div className="p-4 max-h-[600px] overflow-y-auto">
                                        {filteredNonEmployees.length === 0 ? (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                <UserIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                                <p className="text-sm">Aucun utilisateur disponible</p>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                {filteredNonEmployees.map((user) => (
                                                    <div
                                                        key={user.id}
                                                        className="flex items-center justify-between p-3 border rounded-lg dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                                    >
                                                        <div className="flex-1 min-w-0">
                                                            <p className="font-medium text-gray-900 dark:text-white truncate">
                                                                {user.first_name} {user.last_name}
                                                            </p>
                                                            <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                                {user.email}
                                                            </p>
                                                            <div className="flex flex-wrap gap-1 mt-1">
                                                                {user.roles.map((role) => (
                                                                    <span key={role.id} className="px-2 py-0.5 text-xs rounded-full bg-violet-100 text-violet-800 dark:bg-violet-900 dark:text-violet-200">
                                                                        {role.name}
                                                                    </span>
                                                                ))}
                                                            </div>
                                                        </div>
                                                        <Button
                                                            onClick={() => handleAddEmployee(user)}
                                                            size="sm"
                                                            className="ml-3 flex-shrink-0"
                                                            title="Ajouter comme Employé"
                                                        >
                                                            <ArrowRightIcon className="h-5 w-5" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Right Column - Employees (Can Remove) */}
                                <div className="border rounded-lg dark:border-gray-700 overflow-hidden">
                                    <div className="bg-green-50 dark:bg-green-900/20 px-4 py-3 border-b dark:border-gray-700">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3">
                                            Employés actuels ({filteredEmployees.length})
                                        </h3>
                                        <div className="relative">
                                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                            <input
                                                type="text"
                                                placeholder="Rechercher un Employé..."
                                                value={employeeSearch}
                                                onChange={(e) => setEmployeeSearch(e.target.value)}
                                                className="w-full pl-10 pr-4 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                                            />
                                        </div>
                                    </div>
                                    <div className="p-4 max-h-[600px] overflow-y-auto">
                                        {filteredEmployees.length === 0 ? (
                                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                                <UserIcon className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                                <p className="text-sm">Aucun Employé</p>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                {filteredEmployees.map((employee) => (
                                                    <div
                                                        key={employee.uuid}
                                                        className="p-3 border rounded-lg dark:border-gray-700 hover:bg-green-50/50 dark:hover:bg-green-900/10 transition-colors"
                                                    >
                                                        <div className="flex items-start justify-between mb-2">
                                                            <div className="flex-1 min-w-0">
                                                                <p className="font-medium text-gray-900 dark:text-white truncate">
                                                                    {employee.user.first_name} {employee.user.last_name}
                                                                </p>
                                                                <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                                                                    {employee.user.email}
                                                                </p>
                                                            </div>
                                                            <button
                                                                onClick={() => handleRemoveEmployee(employee)}
                                                                className="ml-3 text-red-600 hover:text-red-900 dark:text-red-400 flex-shrink-0"
                                                                title="Retirer l'Employé"
                                                            >
                                                                <TrashIcon className="h-5 w-5" />
                                                            </button>
                                                        </div>
                                                        {(employee.position || employee.job_title || employee.employee_number) && (
                                                            <div className="mt-2 pt-2 border-t dark:border-gray-700 text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                                                {employee.position && (
                                                                    <p>
                                                                        <span className="font-medium">Poste:</span> {employee.position}
                                                                    </p>
                                                                )}
                                                                {employee.job_title && (
                                                                    <p>
                                                                        <span className="font-medium">Titre:</span> {employee.job_title}
                                                                    </p>
                                                                )}
                                                                {employee.employee_number && (
                                                                    <p>
                                                                        <span className="font-medium">Numéro:</span> {employee.employee_number}
                                                                    </p>
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* User Role Dialog */}
            {showUserRoleDialog && selectedUser && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full max-h-[80vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                            Attribuer des rôles à {selectedUser.first_name} {selectedUser.last_name}
                        </h2>
                        <div className="space-y-2 mb-4">
                            {roles.map((role) => (
                                <label key={role.id} className="flex items-center space-x-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={selectedRoles.includes(role.name)}
                                        onChange={(e) => {
                                            if (e.target.checked) {
                                                setSelectedRoles([...selectedRoles, role.name]);
                                            } else {
                                                setSelectedRoles(selectedRoles.filter(r => r !== role.name));
                                            }
                                        }}
                                        className="rounded"
                                    />
                                    <span className="text-gray-900 dark:text-white">{role.name}</span>
                                    <span className="text-xs text-gray-500">({role.permissions.length} permissions)</span>
                                </label>
                            ))}
                        </div>
                        <div className="flex justify-end space-x-2">
                            <Button variant="outline" onClick={() => setShowUserRoleDialog(false)}>Annuler</Button>
                            <Button onClick={handleAssignRoles}>Enregistrer</Button>
                        </div>
                    </div>
                </div>
            )}

            {/* User Permission Dialog */}
            {showUserPermissionDialog && selectedUser && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                        <h2 className="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                            Attribuer des permissions à {selectedUser.first_name} {selectedUser.last_name}
                        </h2>
                        <div className="space-y-4 mb-4">
                            {Object.entries(groupedPermissions).sort().map(([model, perms]) => (
                                <div key={model}>
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2">{model}</h3>
                                    <div className="grid grid-cols-2 gap-2">
                                        {perms.map((permission) => (
                                            <label key={permission.id} className="flex items-center space-x-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={selectedPermissions.includes(permission.name)}
                                                    onChange={(e) => {
                                                        if (e.target.checked) {
                                                            setSelectedPermissions([...selectedPermissions, permission.name]);
                                                        } else {
                                                            setSelectedPermissions(selectedPermissions.filter(p => p !== permission.name));
                                                        }
                                                    }}
                                                    className="rounded"
                                                />
                                                <span className="text-sm text-gray-900 dark:text-white">{permission.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                        <div className="flex justify-end space-x-2">
                            <Button variant="outline" onClick={() => setShowUserPermissionDialog(false)}>Annuler</Button>
                            <Button onClick={handleAssignPermissions}>Enregistrer</Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Create/Edit Role Dialog */}
            {showRoleDialog && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] flex flex-col">
                        {/* Header - Fixed */}
                        <div className="px-6 py-4 border-b dark:border-gray-700">
                            <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                {editingRole ? 'Modifier le rôle' : 'Créer un nouveau rôle'}
                            </h2>
                        </div>

                        {/* Content - Scrollable */}
                        <div className="flex-1 overflow-y-auto px-6 py-4">
                            <input
                                type="text"
                                placeholder="Nom du rôle"
                                value={newRoleName}
                                onChange={(e) => setNewRoleName(e.target.value)}
                                className="w-full mb-4 px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                disabled={editingRole && selectedRole?.name === 'SuperAdmin'}
                            />
                            <div>
                                <p className="text-sm font-medium mb-3 text-gray-900 dark:text-white">Permissions :</p>
                                <div className="space-y-3">
                                    {Object.entries(groupedPermissions).sort().map(([model, perms]) => (
                                        <div key={model} className="border rounded-lg p-3 dark:border-gray-700">
                                            <h3 className="font-semibold text-sm text-gray-900 dark:text-white mb-2">{model}</h3>
                                            <div className="grid grid-cols-2 gap-2">
                                                {perms.map((permission) => (
                                                    <label key={permission.id} className="flex items-center space-x-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer transition-colors">
                                                        <input
                                                            type="checkbox"
                                                            checked={selectedPermissions.includes(permission.name)}
                                                            onChange={(e) => {
                                                                if (e.target.checked) {
                                                                    setSelectedPermissions([...selectedPermissions, permission.name]);
                                                                } else {
                                                                    setSelectedPermissions(selectedPermissions.filter(p => p !== permission.name));
                                                                }
                                                            }}
                                                            className="rounded text-violet-600 focus:ring-violet-500"
                                                        />
                                                        <span className="text-sm text-gray-900 dark:text-white">{permission.name}</span>
                                                    </label>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Footer - Fixed */}
                        <div className="flex justify-end space-x-2 px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <Button variant="outline" onClick={() => {
                                setShowRoleDialog(false);
                                setEditingRole(false);
                                setSelectedRole(null);
                            }}>Annuler</Button>
                            <Button onClick={handleCreateOrUpdateRole}>
                                {editingRole ? 'Modifier' : 'Créer'}
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Create Permission Dialog */}
            {showPermissionDialog && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
                        <h2 className="text-xl font-bold mb-4 text-gray-900 dark:text-white">Créer une nouvelle permission</h2>
                        <input
                            type="text"
                            placeholder="Nom de la permission (ex: edit articles)"
                            value={newPermissionName}
                            onChange={(e) => setNewPermissionName(e.target.value)}
                            className="w-full mb-4 px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        />
                        <div className="flex justify-end space-x-2">
                            <Button variant="outline" onClick={() => setShowPermissionDialog(false)}>Annuler</Button>
                            <Button onClick={handleCreatePermission}>Créer</Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Delete User Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
                onConfirm={handleDeleteUser}
                title="Supprimer l'utilisateur"
                description="Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible et supprimera toutes les données associées à cet utilisateur."
                confirmText="Supprimer"
                cancelText="Annuler"
                isDeleting={isDeleting}
            />

            {/* Status Change Reason Dialog */}
            {showStatusDialog && statusAction && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
                        <h2 className="text-xl font-bold mb-4 text-gray-900 dark:text-white">
                            {statusAction.action === 'activate' && 'Activer l\'utilisateur'}
                            {statusAction.action === 'deactivate' && 'Désactiver l\'utilisateur'}
                            {statusAction.action === 'block' && 'Bloquer l\'utilisateur'}
                            {statusAction.action === 'unblock' && 'Débloquer l\'utilisateur'}
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Veuillez indiquer la raison de cette action. Cette information sera enregistrée et visible dans l'historique de l'utilisateur.
                        </p>
                        <textarea
                            placeholder="Raison du changement de statut (minimum 5 caractères)..."
                            value={statusReason}
                            onChange={(e) => setStatusReason(e.target.value)}
                            className="w-full mb-4 px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white min-h-[100px] resize-none"
                            maxLength={500}
                        />
                        <div className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                            {statusReason.length}/500 caractères
                        </div>
                        <div className="flex justify-end space-x-2">
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setShowStatusDialog(false);
                                    setStatusAction(null);
                                    setStatusReason('');
                                }}
                            >
                                Annuler
                            </Button>
                            <Button
                                onClick={handleToggleUserStatus}
                                disabled={statusReason.length < 5}
                            >
                                Confirmer
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Role Detail Dialog */}
            {showRoleDetailDialog && selectedRoleDetail && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                    <div className="bg-white dark:bg-gray-800 rounded-lg max-w-3xl w-full max-h-[90vh] flex flex-col">
                        {/* Header - Fixed */}
                        <div className="flex justify-between items-center px-6 py-4 border-b dark:border-gray-700">
                            <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                {selectedRoleDetail.name}
                            </h2>
                            <span className="text-sm text-gray-600 dark:text-gray-400">
                                {roleDetailPermissions.length} permissions sélectionnées
                            </span>
                        </div>

                        {/* Content - Scrollable */}
                        <div className="flex-1 overflow-y-auto px-6 py-4">
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Sélectionnez ou désélectionnez les permissions pour ce rôle.
                            </p>

                            {/* Permissions grouped by model */}
                            <div className="space-y-4">
                                {Object.entries(groupedPermissions).sort().map(([model, perms]) => (
                                    <div key={model} className="border rounded-lg p-4 dark:border-gray-700">
                                        <h3 className="font-semibold text-gray-900 dark:text-white mb-3 flex items-center justify-between">
                                            <span>{model}</span>
                                            <span className="text-xs font-normal text-gray-500 dark:text-gray-400">
                                                {perms.filter(p => roleDetailPermissions.includes(p.name)).length}/{perms.length}
                                            </span>
                                        </h3>
                                        <div className="grid grid-cols-2 gap-2">
                                            {perms.map((permission) => (
                                                <label
                                                    key={permission.id}
                                                    className="flex items-center space-x-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer transition-colors"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={roleDetailPermissions.includes(permission.name)}
                                                        onChange={(e) => {
                                                            if (e.target.checked) {
                                                                setRoleDetailPermissions([...roleDetailPermissions, permission.name]);
                                                            } else {
                                                                setRoleDetailPermissions(roleDetailPermissions.filter(p => p !== permission.name));
                                                            }
                                                        }}
                                                        disabled={selectedRoleDetail.name === 'SuperAdmin'}
                                                        className="rounded text-violet-600 focus:ring-violet-500"
                                                    />
                                                    <span className="text-sm text-gray-900 dark:text-white">
                                                        {permission.name}
                                                    </span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Footer - Fixed */}
                        <div className="flex justify-end space-x-2 px-6 py-4 border-t dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <Button
                                variant="outline"
                                onClick={() => {
                                    setShowRoleDetailDialog(false);
                                    setSelectedRoleDetail(null);
                                }}
                            >
                                Annuler
                            </Button>
                            {selectedRoleDetail.name !== 'SuperAdmin' && (
                                <Button onClick={handleSaveRoleDetailPermissions}>
                                    Enregistrer les modifications
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Delete Role Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showDeleteRoleDialog}
                onOpenChange={setShowDeleteRoleDialog}
                onConfirm={handleDeleteRole}
                title="Supprimer le rôle"
                description="Êtes-vous sûr de vouloir supprimer ce rôle ? Cette action est irréversible et tous les utilisateurs ayant ce rôle perdront leurs permissions associées."
                confirmText="Supprimer"
                cancelText="Annuler"
                isDeleting={isDeletingRole}
            />

            {/* Delete Permission Confirmation Dialog */}
            <DeleteConfirmationDialog
                open={showDeletePermissionDialog}
                onOpenChange={setShowDeletePermissionDialog}
                onConfirm={handleDeletePermission}
                title="Supprimer la permission"
                description="Êtes-vous sûr de vouloir supprimer cette permission ? Cette action est irréversible et retirera cette permission de tous les rôles et utilisateurs qui la possèdent."
                confirmText="Supprimer"
                cancelText="Annuler"
                isDeleting={isDeletingPermission}
            />
        </DashboardLayout>
    );
}
