/**
 * Role enum - matches app/Enums/Role.php
 * Keep this in sync with the PHP enum
 */
export enum Role {
    SUPER_ADMIN = 'SuperAdmin',
    ADMIN = 'Admin',
    EDITOR = 'Editor',
    PROJECT_MANAGER = 'ProjectManager',
    EVENT_MANAGER = 'EventManager',
    LIBRARY_MANAGER = 'LibraryManager',
    GROUP_LEADER = 'GroupLeader',
    DEPARTMENT_LEADER = 'DepartmentLeader',
    IMPACT_FAMILY_LEADER = 'ImpactFamilyLeader',
    MEMBER = 'Member',
    STUDENT = 'Student',
    TEACHER = 'Teacher',
}

/**
 * Helper function to check if a user has any of the specified roles
 */
export function hasAnyRole(userRoles: any[] | undefined, roles: Role[]): boolean {
    if (!userRoles || userRoles.length === 0) return false;
    return roles.some(role => {
        return userRoles.some(ur => {
            if (typeof ur === 'string') return ur === role;
            if (ur && typeof ur === 'object' && 'name' in ur) return ur.name === role;
            return false;
        });
    });
}

/**
 * Helper function to check if a user has a specific role
 */
export function hasRole(userRoles: any[] | undefined, role: Role): boolean {
    if (!userRoles || userRoles.length === 0) return false;
    return userRoles.some(ur => {
        if (typeof ur === 'string') return ur === role;
        if (ur && typeof ur === 'object' && 'name' in ur) return ur.name === role;
        return false;
    });
}

/**
 * Helper function to check if user is admin or super admin
 */
export function isAdmin(userRoles: any[] | undefined): boolean {
    return hasAnyRole(userRoles, [Role.ADMIN, Role.SUPER_ADMIN]);
}
