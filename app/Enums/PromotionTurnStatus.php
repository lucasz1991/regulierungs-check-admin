<?php

namespace App\Enums;

enum PromotionTurnStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Released = 'released';
}
