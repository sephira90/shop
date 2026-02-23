<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract\Scenarios;

use App\Support\Smoke\Api\ApiSmokeAssertions;
use App\Support\Smoke\Api\ApiSmokeHttpClient;
use App\Support\Smoke\ApiContract\ApiContractScenario;
use App\Support\Smoke\ApiContract\ApiContractSmokeContext;
use App\Support\Smoke\SmokeCheckResult;
use Illuminate\Http\Response as HttpResponse;

final class AdminProductsApiContractScenario implements ApiContractScenario
{
    /**
     * Execute admin products API contract checks.
     *
     * @return list<SmokeCheckResult>
     */
    public function run(ApiSmokeHttpClient $client, ApiSmokeAssertions $assertions, ApiContractSmokeContext $context): array
    {
        $headers = [
            'Authorization' => 'Bearer '.$context->adminToken,
        ];

        $listResponse = $client->request('GET', '/api/v1/admin/products?per_page=10', [], $headers);
        $assertions->assertStatus($listResponse->status, HttpResponse::HTTP_OK, 'admin products list');
        $assertions->assertHasKeys($listResponse->json, ['data', 'meta'], 'admin products list');
        $assertions->assertMetaShape($listResponse->json['meta'] ?? null, 'admin products list');

        $validationResponse = $client->request('GET', '/api/v1/admin/products?per_page=0', [], $headers);
        $assertions->assertStatus($validationResponse->status, HttpResponse::HTTP_UNPROCESSABLE_ENTITY, 'admin products validation');
        $assertions->assertErrorEnvelope($validationResponse->json, 'admin products validation');

        return [
            new SmokeCheckResult('admin_products_list', $listResponse->status),
            new SmokeCheckResult('admin_products_validation', $validationResponse->status),
        ];
    }
}
