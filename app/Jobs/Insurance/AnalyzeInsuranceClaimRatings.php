<?php

namespace App\Jobs\Insurance;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeInsuranceClaimRatings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public ?int $insuranceId;
    public ?int $insuranceSubtypeId;

    public function __construct(?int $insuranceId = null, ?int $insuranceSubtypeId = null)
    {
        $this->insuranceId        = $insuranceId;
        $this->insuranceSubtypeId = $insuranceSubtypeId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
