<?php

namespace App\Enums;

enum UserRole: string
{
    case EMPLOYEE = 'employee';
    case TEAM_LEAD = 'team_lead';
    case ADMIN = 'admin';
    case HR = 'hr';
}
