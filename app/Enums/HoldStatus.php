<?php

namespace App\Enums;

enum HoldStatus: string
{
    case Active = 'active';
    case Converted = 'converted';
    case Expired = 'expired';
    case Released = 'released';
}
