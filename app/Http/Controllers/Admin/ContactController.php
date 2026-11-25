<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public const MAX_ROWS = 2000;

    public function csv(string $path): array
    {
        $stream = fopen($path, 'rb');
        try {
            $line = fgets($stream);
            if (! $line) {
                throw ValidationException::withMessages(['file' => 'Die Datei ist leer.']);
            }
            $separators = [';', ',', "\t"];
            usort($separators, fn ($a, $b) => count(str_getcsv($line, $b, '"', '')) <=> count(str_getcsv($line, $a, '"', '')));
            rewind($stream);
            $rows = [];
            while (($row = fgetcsv($stream, 0, $separators[0], '"', '')) !== false) {
                if (! array_filter($row, fn ($v) => trim((string) $v) !== '')) {
                    continue;
                }
                $rows[] = array_map(function ($v) {
                    $v = ltrim(trim((string) $v), "\xEF\xBB\xBF");

                    return mb_check_encoding($v, 'UTF-8') ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
                }, $row);
                if (count($rows) > self::MAX_ROWS + 1) {
                    throw ValidationException::withMessages(['file' => 'Bitte die Datei in Abschnitte mit höchstens 2.000 Kontakten teilen.']);
                }
            }
            $headers = array_shift($rows) ?? [];

            return [$headers, $rows];
        } finally {
            fclose($stream);
        }
    }

    public function columnMap(array $headers): array
    {
        $aliases = ['e-mail-adresse' => 'email', 'mail' => 'email', 'e-mail' => 'email', 'telefonnummer' => 'phone', 'stadt' => 'city', 'adresse' => 'address', 'kategorie' => 'category', 'unternehmen' => 'company'];
        foreach (Contact::FIELDS as $key => $label) {
            $aliases[$key] = $key;
            $aliases[mb_strtolower($label)] = $key;
        }

        return array_map(fn ($h) => $aliases[mb_strtolower(trim($h))] ?? '', $headers);
    }

    private function document(string $html): DOMXPath
    {
        $doc = new DOMDocument;
        @$doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);

        return new DOMXPath($doc);
    }

    public function html(string $html): array
    {
        $xpath = $this->document($html);
        $nodes = $xpath->query('//article[contains(@class,"mod-Treffer")]');
        if (! $nodes->length) {
            $nodes = $xpath->query('//body[.//h1]');
        }
        if ($nodes->length > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'Maximal 2.000 Kontakte je Datei.']);
        }
        $rows = [];
        foreach ($nodes as $node) {
            $rows[] = $this->htmlRow($xpath, $node);
        }

        return $rows;
    }

    private function htmlRow(DOMXPath $xpath, DOMElement $node): array
    {
        $value = fn ($query) => trim((string) $xpath->evaluate('string(('.$query.')[1])', $node));
        $detail = $value('.//a[contains(@href,"/gsbiz/")]/@href');
        $mailto = $value('.//a[starts-with(@href,"mailto:")]/@href | .//*[@id="email_versenden"]/@data-link');

        return [
            'name' => $value('.//h1 | .//h2 | .//h3 | .//*[@itemprop="name"]'),
            'address' => $value('.//address'), 'category' => $value('.//*[contains(@class,"branchen")]'),
            'phone' => $value('.//*[@data-role="telefonnummer"]') ?: preg_replace('/^tel:/i', '', $value('.//a[starts-with(@href,"tel:")]/@href')),
            'email' => rawurldecode(explode('?', preg_replace('/^mailto:/i', '', $mailto))[0]),
            'website' => $value('.//a[contains(@data-wipe-realview,"webadresse")]/@href'),
            '_detail' => str_starts_with($detail, '/gsbiz/') ? 'https://www.gelbeseiten.de'.$detail : $detail,
        ];
    }

    public function detail(string $url): array
    {
        if (! preg_match('~^https://(?:www\.)?gelbeseiten\.de/gsbiz/[a-z0-9/_-]+(?:\?[^\s]*)?$~iD', $url)) {
            return [];
        }
        $response = Http::withoutRedirecting()->connectTimeout(2)->timeout(4)->get($url);
        if (! $response->successful()) {
            return [];
        }

        return array_filter($this->html($response->body())[0] ?? [], fn ($v) => $v !== '');
    }
}
