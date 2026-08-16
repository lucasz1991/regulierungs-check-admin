<?php

namespace App\Services\Promotion;

use App\Enums\PromotionTicketStatus;
use App\Models\PromotionParticipation;
use App\Models\PromotionTicket;
use App\Support\Promotion\ParticipationId;
use DomainException;

final class PromotionTicketQrSigner
{
    public const VERSION = 'RC-TICKET-V1';

    public function __construct(
        private readonly PromotionSettingsService $settings,
        private readonly PromotionAuditChain $audit,
    ) {}

    public function payload(PromotionTicket $ticket): string
    {
        $ticket = PromotionTicket::query()->with(['participation', 'campaign'])->findOrFail($ticket->getKey());
        if ($ticket->status !== PromotionTicketStatus::Ready || ! $ticket->participation || ! $ticket->campaign) {
            throw new DomainException('Nur ein bereites Ticket kann als QR-Code angezeigt werden.');
        }
        if (! $this->audit->verify($ticket->campaign)) {
            throw new DomainException('Die Ticket- oder Auditdaten sind ungueltig.');
        }

        $publicId = (string) $ticket->participation->public_id;

        return self::VERSION.':'.$publicId.':'.$this->signature($publicId, (int) $ticket->campaign_id);
    }

    public function parse(string $payload): PromotionTicket
    {
        $payload = trim($payload);
        if (! preg_match('/\A'.preg_quote(self::VERSION, '/').':([^:]+):([A-Za-z0-9_-]{43})\z/', $payload, $matches)
            || ! ParticipationId::isValid($matches[1])) {
            throw new DomainException('Der Ticket-QR-Code ist ungueltig.');
        }

        $participation = PromotionParticipation::query()->where('public_id', $matches[1])->first();
        $ticket = $participation?->ticket()->first();
        if (! $ticket || $ticket->status !== PromotionTicketStatus::Ready) {
            throw new DomainException('Das Ticket wurde bereits verwendet, storniert oder ist ungueltig.');
        }

        $expected = $this->signature((string) $participation->public_id, (int) $ticket->campaign_id);
        if (! hash_equals($expected, $matches[2])) {
            throw new DomainException('Die Ticket-Signatur ist ungueltig.');
        }

        return $ticket->load(['participation', 'campaign']);
    }

    private function signature(string $publicId, int $campaignId): string
    {
        $binary = hash_hmac('sha256', self::VERSION."\n".$campaignId."\n".$publicId, $this->settings->auditKey(), true);

        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
