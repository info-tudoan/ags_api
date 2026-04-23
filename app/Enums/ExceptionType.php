<?php

namespace App\Enums;

enum ExceptionType: string
{
    case DELAY_OVER_2H = 'delay_over_2h';
    case DELAY_UNDER_2H = 'delay_under_2h';
    case GPS_ANOMALY = 'gps_anomaly';
    case MANUAL_CORRECTION = 'manual_correction';
    case OUT_OF_ZONE = 'out_of_zone';
}
