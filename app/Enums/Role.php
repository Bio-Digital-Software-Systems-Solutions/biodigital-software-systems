<?php

namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'SuperAdmin';
    case ADMIN = 'admin';
    case EDITOR = 'Editor';
    case PROJECT_MANAGER = 'project-manager';
    case EVENT_MANAGER = 'event-manager';
    case LIBRARY_MANAGER = 'library-manager';
    case GROUP_LEADER = 'group-leader';
    case DEPARTMENT_LEADER = 'department-leader';
    case IMPACT_FAMILY_LEADER = 'impact-family-leader';
    case MEMBER = 'member';
    case PASTOR = 'pastor';
    case STUDENT = 'student';
    case TEACHER = 'teacher';
    case WRITER = 'writer';
    case EMPLOYEE = 'employee';
    case STAR = 'star';
}
