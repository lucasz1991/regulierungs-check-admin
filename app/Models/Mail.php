<?php

namespace App\Models;

use App\Jobs\ProcessMailJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Mail extends Model
{
    protected $fillable = ['type', 'status', 'content', 'recipients', 'dispatch_key'];

    protected $casts = ['content' => 'array', 'recipients' => 'array', 'status' => 'boolean'];

    protected static function booted(): void
    {
        static::created(function (Mail $mail) {
            foreach (array_keys($mail->recipients) as $index) {
                ProcessMailJob::dispatch($mail, $index)->beforeCommit();
            }
        });
    }

    public function retryFailed(): int
    {
        return DB::transaction(function () {
            $mail = self::query()->lockForUpdate()->findOrFail($this->id);
            $recipients = $mail->recipients;
            $count = 0;
            foreach ($recipients as $index => &$recipient) {
                if (($recipient['state'] ?? '') !== 'failed') {
                    continue;
                }
                $recipient['state'] = 'pending';
                ProcessMailJob::dispatch($mail, $index)->beforeCommit();
                $count++;
            }
            unset($recipient);
            if ($count) {
                $mail->update(['status' => false, 'recipients' => $recipients]);
            }

            return $count;
        });
    }
}
