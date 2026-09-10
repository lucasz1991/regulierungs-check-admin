<?php

namespace App\Enums;

enum PromotionNotificationStatus: string
{
    case Open = 'open';
    case Sent = 'sent';
    case Failed = 'failed';
}
