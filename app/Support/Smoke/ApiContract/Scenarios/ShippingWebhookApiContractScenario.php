<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract\Scenarios;

use App\Support\Smoke\Api\ApiSmokeAssertions;
use App\Support\Smoke\Api\ApiSmokeHttpClient;
use App\Support\Smoke\ApiContract\ApiContractScenario;
use App\Support\Smoke\ApiContract\ApiContractSmokeContext;
use App\Support\Smoke\SmokeCheckResult;
use Illuminate\Http\Response as HttpResponse;

final class ShippingWebhookApiContractScenario implements ApiContractScenario
{
    /**
     * Execute shipping webhook API contract checks.
     *
     * @return list<SmokeCheckResult>
     */
    public function run(ApiSmokeHttpClient $client, ApiSmokeAssertions $assertions, ApiContractSmokeContext $context): array
    {
        $response = $client->request('POST', '/api/v1/webhooks/shipping', [
            'event_id' => 'evt-contract-smoke',
            'tracking_number' => 'trk-contract-smoke',
            'status' => 'shipped',
        ]);

        $assertions->assertStatus($response->status, HttpResponse::HTTP_BAD_REQUEST, 'shipping webhook missing signature');
        $assertions->assertErrorEnvelope($response->json, 'shipping webhook missing signature');

        return [
            new SmokeCheckResult('shipping_webhook_missing_signature', $response->status),
        ];
    }
}
