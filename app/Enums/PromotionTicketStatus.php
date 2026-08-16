<?php

namespace App\Enums;

enum PromotionTicketStatus: string
{
    case Ready = 'ready';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
