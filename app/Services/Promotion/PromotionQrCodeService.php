<?php

namespace App\Services\Promotion;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use RuntimeException;

final class PromotionQrCodeService
{
    public function __construct(private readonly PromotionSettingsService $settings)
    {
    }

    public function redemptionUrl(string $plainToken): string
    {
        return $this->baseUrl().'/promotion/einloesen/'.rawurlencode($plainToken);
    }

    public function assertConfigured(): void
    {
        $this->baseUrl();
    }

    private function baseUrl(): string
    {
        return $this->settings->redemptionBaseUrl();
    }

    public function svg(string $url): string
    {
        $renderer = new ImageRenderer(new RendererStyle(360, 2), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($url);
    }
}
