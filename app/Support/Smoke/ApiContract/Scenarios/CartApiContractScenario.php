<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract\Scenarios;

use App\Support\Data\TypedValue;
use App\Support\Smoke\Api\ApiSmokeAssertions;
use App\Support\Smoke\Api\ApiSmokeHttpClient;
use App\Support\Smoke\ApiContract\ApiContractScenario;
use App\Support\Smoke\ApiContract\ApiContractSmokeContext;
use App\Support\Smoke\SmokeCheckResult;
use Illuminate\Http\Response as HttpResponse;

final class CartApiContractScenario implements ApiContractScenario
{
    /**
     * Execute cart API contract checks.
     *
     * @return list<SmokeCheckResult>
     */
    public function run(ApiSmokeHttpClient $client, ApiSmokeAssertions $assertions, ApiContractSmokeContext $context): array
    {
        $response = $client->request('GET', '/api/v1/cart');

        $assertions->assertStatus($response->status, HttpResponse::HTTP_OK, 'cart show');
        $assertions->assertHasKeys($response->json, ['data'], 'cart show');
        $assertions->assertHasKeys(TypedValue::associativeArray($response->json['data'] ?? []), ['id', 'currency', 'items', 'summary'], 'cart show payload');

        return [
            new SmokeCheckResult('cart_show', $response->status),
        ];
    }
}
