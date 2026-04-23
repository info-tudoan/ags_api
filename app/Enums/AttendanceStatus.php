<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case ON_TIME = 'on_time';
    case EARLY = 'early';
    case DELAYED = 'delayed';
    case ABSENT = 'absent';
}
