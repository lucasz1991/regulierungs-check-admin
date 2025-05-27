<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Insurance;

class AnalyzeInsuranceOnlineViaGpt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Insurance $insurance;

    public function __construct(Insurance $insurance)
    {
        $this->insurance = $insurance;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
