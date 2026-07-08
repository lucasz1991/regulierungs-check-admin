<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MediaController extends Controller
{
    protected array $apiSettings = [];

    public function __construct()
    {
        $this->apiSettings['base_api_url'] = $this->settingValue('base_api_url');
        $this->apiSettings['base_api_key'] = $this->settingValue('base_api_key');
    }

    protected function settingValue(string $key): ?string
    {
        $value = Setting::where('key', $key)->value('value');

        if (is_array($value)) {
            $value = $value['value'] ?? reset($value);
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = is_array($decoded) ? ($decoded['value'] ?? reset($decoded)) : $decoded;
            }
        }

        return filled($value) ? (string) $value : null;
    }

    protected function baseEndpoint(string $path): ?string
    {
        $url = rtrim((string) ($this->apiSettings['base_api_url'] ?? ''), '/');
        $key = $this->apiSettings['base_api_key'] ?? null;

        if ($url === '' || ! $key) {
            return null;
        }

        return $url . $path;
    }

    protected function missingBaseConfigurationResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Base-API ist nicht konfiguriert.',
        ], 422);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:40960',
            'folder' => 'nullable|string',
            'visibility' => 'nullable|in:public,private',
        ]);

        $endpoint = $this->baseEndpoint('/api/admin/upload');
        if (! $endpoint) {
            return $this->missingBaseConfigurationResponse();
        }

        $file = $request->file('file');
        $requestBuilder = Http::timeout(120)->attach(
            'file',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->withHeaders([
            'X-API-KEY' => $this->apiSettings['base_api_key'],
        ])->withoutVerifying();

        $payload = array_filter([
            'folder' => $request->input('folder'),
            'visibility' => $request->input('visibility'),
        ], fn ($value) => filled($value));

        $response = $requestBuilder->post($endpoint, $payload);

        return $response->successful()
            ? response()->json($response->json())
            : response()->json(['success' => false, 'message' => 'Upload fehlgeschlagen.'], $response->status());
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'visibility' => 'nullable|in:public,private',
        ]);

        $endpoint = $this->baseEndpoint('/api/admin/delete');
        if (! $endpoint) {
            return $this->missingBaseConfigurationResponse();
        }

        $payload = ['path' => $request->path];
        if ($request->filled('visibility')) {
            $payload['visibility'] = $request->input('visibility');
        }

        $response = Http::timeout(60)->withHeaders([
            'X-API-KEY' => $this->apiSettings['base_api_key'],
        ])->withoutVerifying()->delete($endpoint, $payload);

        return $response->successful()
            ? response()->json($response->json())
            : response()->json(['success' => false, 'message' => 'Loeschen fehlgeschlagen.'], $response->status());
    }

    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'file_id' => 'required_without:url|nullable|integer|min:1',
            'url' => 'required_without:file_id|nullable|string|max:2048',
            'expires' => 'nullable|integer|min:30|max:86400',
            'disk' => 'nullable|in:private,public',
        ]);

        $endpoint = $this->baseEndpoint('/api/admin/resolve-file-url');
        if (! $endpoint) {
            return $this->missingBaseConfigurationResponse();
        }

        $payload = array_filter([
            'expires' => $request->input('expires'),
            'disk' => $request->input('disk'),
        ], fn ($value) => filled($value));

        if ($request->filled('file_id')) {
            $payload['file_id'] = (int) $request->input('file_id');
        } elseif ($request->filled('url')) {
            $payload['url'] = $request->input('url');
        }

        $response = Http::timeout(60)->withHeaders([
            'X-API-KEY' => $this->apiSettings['base_api_key'],
        ])->withoutVerifying()->post($endpoint, $payload);

        return $response->successful()
            ? response()->json($response->json())
            : response()->json([
                'success' => false,
                'message' => 'Aufloesung fehlgeschlagen.',
                'status' => $response->status(),
            ], $response->status());
    }
}
