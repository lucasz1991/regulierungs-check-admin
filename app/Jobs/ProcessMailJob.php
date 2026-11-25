<?php

namespace App\Jobs;

use App\Models\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // The Base worker deserializes this job and provides its handle() method.
    public function __construct(public Mail $mail, public ?int $recipientIndex = null)
    {
        $this->onConnection('database')->onQueue('kundenmail');
    }
}
