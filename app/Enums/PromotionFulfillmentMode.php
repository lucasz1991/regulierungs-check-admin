<?php

namespace App\Enums;

enum PromotionFulfillmentMode: string
{
    case OnsiteStaff = 'onsite_staff';
    case ExternalAdmin = 'external_admin';
}
