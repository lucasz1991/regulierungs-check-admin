<?php

namespace App\Enums;

enum PromotionGiftCodeStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sent = 'sent';
    case Failed = 'failed';
    case Disabled = 'disabled';
}
