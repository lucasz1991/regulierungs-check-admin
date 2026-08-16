<?php

namespace App\Enums;

enum PromotionQuotaPolicy: string
{
    case Block = 'block';
    case StickerContinue = 'sticker_continue';
}
