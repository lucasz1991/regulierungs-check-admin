<?php

namespace App\Enums;

enum PromotionDigitalDeliveryStatus: string
{
    case NotApplicable = 'not_applicable';
    case AwaitingApproval = 'awaiting_approval';
    case AwaitingProfile = 'awaiting_profile';
    case AwaitingCode = 'awaiting_code';
    case Reserved = 'reserved';
    case Sent = 'sent';
    case Failed = 'failed';
}
