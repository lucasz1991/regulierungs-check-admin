<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\ClaimRating;

class ClaimRatingAIEval implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public ClaimRating $claimRating;
    public bool $isAdminReanalysis;
    /**
     * Create a new job instance.
     */
    public function __construct(ClaimRating $claimRating, bool $isAdminReanalysis = false)
    {
        $this->claimRating = $claimRating;
        $this->isAdminReanalysis = $isAdminReanalysis;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
    }
}
