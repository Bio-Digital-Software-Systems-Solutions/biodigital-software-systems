/**
 * Permission helpers - compatible with both string and Permission object types
 */

import { Permission as PermissionType, Role as RoleType, User } from '@/Types/models';
import { Role } from './Role';

/**
 * Helper function to check if a user has a specific permission
 */
export function userHasPermission(user: User | null | undefined, permission: string): boolean {
    if (!user) return false;

    // Check if user is admin - admins have all permissions
    if (user.roles?.some(role => {
        const roleName = typeof role === 'string' ? role : role.name;
        return roleName === Role.ADMIN || roleName === Role.SUPER_ADMIN;
    })) {
        return true;
    }

    // Check if user has the specific permission
    return user.permissions?.some(p => {
        if (typeof p === 'string') return p === permission;
        if (p && typeof p === 'object' && 'name' in p) return p.name === permission;
        return false;
    }) || false;
}

/**
 * Helper function to check if a user has any of the specified permissions
 */
export function userHasAnyPermission(user: User | null | undefined, permissions: string[]): boolean {
    if (!user) return false;
    return permissions.some(permission => userHasPermission(user, permission));
}

/**
 * Helper function to check if a user has all of the specified permissions
 */
export function userHasAllPermissions(user: User | null | undefined, permissions: string[]): boolean {
    if (!user) return false;
    return permissions.every(permission => userHasPermission(user, permission));
}

/**
 * Helper function to check if a user has a specific role
 */
export function userHasRole(user: User | null | undefined, role: Role | string): boolean {
    if (!user) return false;

    return user.roles?.some(r => {
        if (typeof r === 'string') return r === role;
        if (r && typeof r === 'object' && 'name' in r) return r.name === role;
        return false;
    }) || false;
}

/**
 * Helper function to check if a user has any of the specified roles
 */
export function userHasAnyRole(user: User | null | undefined, roles: (Role | string)[]): boolean {
    if (!user) return false;
    return roles.some(role => userHasRole(user, role));
}

/**
 * Helper function to check if user is admin or super admin
 */
export function userIsAdmin(user: User | null | undefined): boolean {
    return userHasAnyRole(user, [Role.ADMIN, Role.SUPER_ADMIN]);
}

/**
 * Check if permissions array includes a specific permission (handles both string[] and Permission[])
 */
export function permissionsIncludes(permissions: any[] | undefined, permissionName: string): boolean {
    if (!permissions) return false;

    return permissions.some(p => {
        if (typeof p === 'string') return p === permissionName;
        if (p && typeof p === 'object' && 'name' in p) return p.name === permissionName;
        return false;
    });
}

/**
 * Check if roles array includes a specific role (handles both string[] and Role[])
 */
export function rolesIncludes(roles: any[] | undefined, roleName: string): boolean {
    if (!roles) return false;

    return roles.some(r => {
        if (typeof r === 'string') return r === roleName;
        if (r && typeof r === 'object' && 'name' in r) return r.name === roleName;
        return false;
    });
}
