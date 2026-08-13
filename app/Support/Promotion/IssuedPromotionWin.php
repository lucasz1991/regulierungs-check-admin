<?php

namespace App\Support\Promotion;

use App\Models\PromotionWin;

final readonly class IssuedPromotionWin
{
    public function __construct(
        public PromotionWin $win,
        public string $plainToken,
    ) {}
}
