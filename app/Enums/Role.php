<?php

namespace App\Enums;

enum Role: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case WRITER = 'writer';
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
    case EMPLOYEE = 'employee';
    case STAR = 'star';
    case MLR_AGENT = 'mlr-agent';
    case ACCOUNTANT = 'accountant';
    case PRODUCT_OWNER = 'product-owner';
}
