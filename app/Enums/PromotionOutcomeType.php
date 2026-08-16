<?php

namespace App\Enums;

enum PromotionOutcomeType: string
{
    case Prize = 'prize';
    case NoWin = 'no_win';
    case Retry = 'retry';
    case QuotaReroll = 'quota_reroll';

    public function isFinal(): bool
    {
        return in_array($this, [self::Prize, self::NoWin], true);
    }
}
