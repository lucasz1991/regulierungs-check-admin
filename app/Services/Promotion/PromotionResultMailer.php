<?php

namespace App\Services\Promotion;

use App\Enums\PromotionOutcomeType;
use App\Mail\PromotionResultMail;
use App\Models\PromotionSpinResult;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class PromotionResultMailer
{
    public function __construct(
        private readonly PromotionTurnService $turns,
        private readonly PromotionSettingsService $settings,
    ) {}

    public function send(PromotionSpinResult $result, bool $correction = false): bool
    {
        $result = $result->fresh([
            'ticket.participation',
            'ticket.user',
            'campaign',
        ]);

        if (! $result || ! $result->is_final || ! $this->isMailOutcome($result)) {
            return true;
        }

        try {
            $email = trim((string) $result->ticket?->user?->email);
            if ($email === '') {
                throw new \RuntimeException('Zum Ticket ist keine Empfaengeradresse vorhanden.');
            }

            $participantUrl = rtrim($this->settings->redemptionBaseUrl(), '/').'/gluecksrad';
            Mail::to($email)->send(new PromotionResultMail($result, $correction, $participantUrl));
        } catch (Throwable $exception) {
            $this->markFailedWithoutThrowing($result, $exception);
            report($exception);

            return false;
        }

        try {
            $this->turns->markMailSent($result);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function resend(PromotionSpinResult $result, User $admin): bool
    {
        try {
            $result = $this->turns->markMailPendingForResend($result, $admin);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }

        return $this->send($result, $result->corrects_result_id !== null);
    }

    private function markFailedWithoutThrowing(PromotionSpinResult $result, Throwable $exception): void
    {
        try {
            $this->turns->markMailFailed($result, $exception::class.'|'.$exception->getMessage());
        } catch (Throwable $transitionException) {
            report($transitionException);
        }
    }

    private function isMailOutcome(PromotionSpinResult $result): bool
    {
        $outcome = $result->outcome_type_snapshot;
        $value = $outcome instanceof \BackedEnum ? $outcome->value : (string) $outcome;

        return in_array($value, [PromotionOutcomeType::Prize->value, PromotionOutcomeType::NoWin->value], true);
    }
}
