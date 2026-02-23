<?php

declare(strict_types=1);

namespace App\Support\Smoke\ApiContract\Scenarios;

use App\Support\Smoke\Api\ApiSmokeAssertions;
use App\Support\Smoke\Api\ApiSmokeHttpClient;
use App\Support\Smoke\ApiContract\ApiContractScenario;
use App\Support\Smoke\ApiContract\ApiContractSmokeContext;
use App\Support\Smoke\SmokeCheckResult;
use Illuminate\Http\Response as HttpResponse;

final class CatalogApiContractScenario implements ApiContractScenario
{
    /**
     * Execute catalog API contract checks.
     *
     * @return list<SmokeCheckResult>
     */
    public function run(ApiSmokeHttpClient $client, ApiSmokeAssertions $assertions, ApiContractSmokeContext $context): array
    {
        $listResponse = $client->request('GET', '/api/v1/catalog/products?per_page=5');

        $assertions->assertStatus($listResponse->status, HttpResponse::HTTP_OK, 'catalog list');
        $assertions->assertHasKeys($listResponse->json, ['data', 'meta'], 'catalog list');
        $assertions->assertMetaShape($listResponse->json['meta'] ?? null, 'catalog list');

        $notFoundResponse = $client->request('GET', '/api/v1/catalog/products/does-not-exist-smoke');

        $assertions->assertStatus($notFoundResponse->status, HttpResponse::HTTP_NOT_FOUND, 'catalog show missing');
        $assertions->assertErrorEnvelope($notFoundResponse->json, 'catalog show missing');

        return [
            new SmokeCheckResult('catalog_products_list', $listResponse->status),
            new SmokeCheckResult('catalog_product_not_found', $notFoundResponse->status),
        ];
    }
}
