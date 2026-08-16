<?php

namespace App\Enums;

enum PromotionMailStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case NotRequired = 'not_required';
}
