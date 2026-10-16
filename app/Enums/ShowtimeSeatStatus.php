<?php

namespace App\Enums;

enum ShowtimeSeatStatus: string
{
    case Available = 'available';
    case Held = 'held';
    case Sold = 'sold';
}
