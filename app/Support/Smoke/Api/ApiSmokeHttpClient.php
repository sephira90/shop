<?php

declare(strict_types=1);

namespace App\Support\Smoke\Api;

use App\Support\Data\TypedValue;
use App\Support\Observability\ObservabilityService;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;

final class ApiSmokeHttpClient
{
    /**
     * Create API smoke HTTP client.
     */
    public function __construct(
        private readonly HttpKernel $httpKernel,
        private readonly ObservabilityService $observabilityService,
    ) {}

    /**
     * Execute one JSON API request through HTTP kernel.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    public function request(string $method, string $uri, array $payload = [], array $headers = []): ApiSmokeResponse
    {
        $normalizedMethod = strtoupper($method);
        $startedAt = hrtime(true);

        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];

        foreach ($headers as $name => $value) {
            $normalized = 'HTTP_'.strtoupper(str_replace('-', '_', $name));
            if ($normalized === 'HTTP_CONTENT_TYPE') {
                $normalized = 'CONTENT_TYPE';
            }

            $server[$normalized] = $value;
        }

        $content = $normalizedMethod === 'GET'
            ? null
            : json_encode($payload, JSON_THROW_ON_ERROR);

        $request = Request::create($uri, $normalizedMethod, [], [], [], $server, $content);
        if ($normalizedMethod === 'GET' && $payload !== []) {
            $request->query->add($payload);
        }

        $response = $this->httpKernel->handle($request);
        $this->httpKernel->terminate($request, $response);

        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
        $this->observabilityService->apiRequest(
            method: $normalizedMethod,
            path: '/'.ltrim((string) $request->path(), '/'),
            status: $response->getStatusCode(),
            durationMs: $durationMs,
            source: 'smoke',
        );

        $decoded = json_decode((string) $response->getContent(), true);
        $json = is_array($decoded) ? TypedValue::associativeArray($decoded) : [];

        return new ApiSmokeResponse($response->getStatusCode(), $json);
    }
}
