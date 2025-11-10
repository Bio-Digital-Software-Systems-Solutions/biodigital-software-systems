<?php

namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'SuperAdmin';
    case ADMIN = 'Admin';
    case EDITOR = 'Editor';
    case PROJECT_MANAGER = 'ProjectManager';
    case EVENT_MANAGER = 'EventManager';
    case LIBRARY_MANAGER = 'LibraryManager';
    case GROUP_LEADER = 'GroupLeader';
    case DEPARTMENT_LEADER = 'DepartmentLeader';
    case IMPACT_FAMILY_LEADER = 'ImpactFamilyLeader';
    case MEMBER = 'Member';
    case PASTOR = 'Pastor';
    case STUDENT = 'Student';
    case TEACHER = 'Teacher';
}
