# Wave C1 — Catalog Module

**Status:** Active — execution starts after this plan is approved.
**Scope owner:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` → Convergence Waves → C1.
**Sequence constraint:** entry criteria met (`C0` closed; module-boundary guardrail active).

## Verified baseline (`2026-07-05`, mapping by explore agent)

Migration surface (public Catalog read slice only; admin catalog writes stay):

- **Transport (2 files):** `app/Http/Controllers/Api/V1/CatalogController.php` (3 methods: `index`, `show`, `categories`; constructor-depends on 3 query handlers); `app/Http/Requests/Catalog/CatalogIndexRequest.php` (5 filter rules + `filter()` + `perPage()`).
- **Routes:** `routes/api.php:15,41-45` — 3 routes under `catalog` prefix with `throttle:search` + `cache.headers:public;max_age=60;etag`. No route names. No route-model binding (`{slug}` is string).
- **Application (14 files under `app/Application/Catalog/`):** 1 contract (`CatalogProductReadRepository`), 8 DTOs (`CatalogProductListFilterDto`, `CatalogProductPaginatedResultDto`, `CatalogProductResultDto`, `CatalogProductVariantResultDto`, `CatalogProductVariantInventoryResultDto`, `CatalogProductCategoryResultDto`, `CatalogCategoryResultDto`, `CatalogCategoriesResultDto`), 3 query handlers, 2 query payloads.
- **Repository (1 file):** `app/Repositories/CatalogProductReadRepository.php` (stateless, implements `App\Application\Catalog\Contracts\CatalogProductReadRepository`).
- **Service (2 files):**
  - `CatalogService` — consumed ONLY by the moving slice + 2 performance-smoke scenarios. Moves wholesale.
  - `CatalogVersionService` — consumed by `CatalogService` (in-slice) AND by 3 admin services (`AdminCatalogService`, `AdminCategoryService`, `AdminCacheService`). Moves, but with a new contract surface for admin.
- **DI binding:** `app/Providers/ApplicationBindingsServiceProvider.php:41` binds `CatalogProductReadRepository` contract → implementation. Moves to new `CatalogServiceProvider`.
- **Tests (2 files):** `tests/Feature/CatalogTest.php` (mixed — bundles an unrelated SPA-shell test that must be extracted); `tests/Unit/CatalogVersionServiceTest.php` (references `'catalog:version'` cache key 6×).
- **Frontend:** untouched. API paths (`/api/v1/catalog/products`, `/api/v1/catalog/products/{slug}`, `/api/v1/catalog/categories`) and DTO shapes are locked by `docs/api/openapi.yaml` and the S1 conformance suite; the relocation must not change any byte of the wire contract.
- **Eloquent model imports:** `Product`, `ProductVariant`, `Category`, `Inventory` (all stay in `App\Models\*` per C0; model-ownership wave is post-C7).
- **Smoke dependencies:** `app/Support/Smoke/Performance/Scenarios/CatalogList{Cold,Warm}PerformanceSmokeScenario.php` import `CatalogService` directly (cross-module violation after move). `ApiContractSmokeScenarioRegistry` + `ApiContractSmokeContextFactory` reference catalog only by path strings and seeder — no class import. `PerformanceSmokeSetupFactory` + `PerformanceSmokeContextDto` use `CatalogProductListFilterDto::fromValidated()` — that DTO becomes part of the Catalog contract surface.
- **Other cross-references:** `psalm.xml:49` per-file suppression for `CatalogService` (must move namespace); `RepositoryReadBoundaryTest:43` forbidden-namespace string must update post-move; `tests/Concerns/CreatesCatalogVariant.php` shared test trait stays.

## Resolved design decisions (`2026-07-05`)

1. **`CatalogService` is a module-private service.** It moves into `app/Domains/Catalog/Services/CatalogService.php` with no contract — it is consumed only by Catalog handlers and by the two performance-smoke scenarios (see decision 4).
2. **`CatalogVersionService` exposes a contract.** New `App\Domains\Catalog\Contracts\CatalogCacheVersion` interface with `current(): int` and `bump(): int`. The implementation lives in `app/Domains/Catalog/Services/CatalogVersionService.php`. The three admin services update their constructor type from concrete to contract — atomic with the namespace move.
3. **`CatalogProductReadRepository` contract keeps `Product` at the boundary** as a documented shared-kernel allowance. C0 explicitly states "module code depends on [Eloquent models] only through its repository"; the post-C7 model-ownership wave is the place to revisit. The contract surface is otherwise DTO/scalar only.
4. **Performance-smoke scenarios get a contract.** The two scenarios live in `App\Support\Smoke\Performance\Scenarios\*` (allowlisted infrastructure). After `CatalogService` moves to `App\Domains\Catalog\Services\`, importing it cross-module violates the guardrail. New contract `App\Domains\Catalog\Contracts\CatalogReadService` exposed by the module; `CatalogService` implements it; the two scenarios depend on the contract. This is the only way to keep the production-grade smoke scenarios wired without rewriting them to hit HTTP (which would defeat their purpose — they measure service-level query budgets, not HTTP latency).
5. **`CatalogProductListFilterDto` is part of the public contract surface** because `PerformanceSmokeSetupFactory` and `PerformanceSmokeContextDto` (in `App\Support\*`) construct it. It moves to `App\Domains\Catalog/Contracts/Dto/CatalogProductListFilterDto.php`. Other DTOs are module-private (consumed only by handlers) — they move to `app/Domains/Catalog/Application/Dto/`.
6. **Query payloads move to `app/Domains/Catalog/Application/Queries/`** alongside their handlers — module-internal, no cross-module exposure.
7. **`CatalogController` becomes `final`** during the move (every other class in the slice is already `final`/`final readonly`; no consumers extend it; AGENTS.md says `final` by default).
8. **`CatalogIndexRequest` stays at `app/Domains/Catalog/Controllers/CatalogIndexRequest.php`** — it is the transport validation boundary, owned by the module's transport layer. (Alternative: keep it under `app/Http/Requests/Catalog/` and let the module controller import it cross-module via the legacy-bridge allowlist. Rejected: the request is part of the catalog transport slice and should move with it; the allowlist is for legacy code, not for newly-moved code.)
9. **Smoke factory + context DTO keep using `CatalogProductListFilterDto`** through the public contract path. They already live in `App\Support\*` which is allowlisted, so importing `App\Domains\Catalog\Contracts\Dto\*` is a permitted cross-module Contracts import (the guardrail allows `<OtherModule>\Contracts\*`).
10. **`tests/Feature/CatalogTest.php` splits:** the SPA-shell cache test moves to a new `tests/Feature/SpaShellCacheTest.php` (or similar); the catalog-only test stays as `tests/Feature/CatalogTest.php` and updates its imports to the new namespace.
11. **`CatalogServiceProvider` registers in `bootstrap/providers.php`** immediately after `ApplicationBindingsServiceProvider`. It binds: `CatalogProductReadRepository` → implementation, `CatalogCacheVersion` → `CatalogVersionService`, `CatalogReadService` → `CatalogService`.
12. **The `'catalog:version'` cache key is a stable operational contract.** The key string does not change during the move. `CatalogVersionService` keeps `CACHE_KEY = 'catalog:version'` as-is; `tests/Unit/CatalogVersionServiceTest.php` keeps asserting against it.

## Invariants to preserve

1. **Wire contract byte-identical.** Three `/api/v1/catalog/*` endpoints: paths, verbs, request schemas, response schemas, status codes, throttle (`search`), cache-control (`public, max-age=60`). Verified by `OpenApiConformanceFeatureTest` (S1).
2. **Cache key `catalog:version` unchanged** — admin writes and catalog reads share this key through the new `CatalogCacheVersion` contract; bumping the version invalidates the read cache exactly as today.
3. **Admin write behavior unchanged.** `AdminCatalogService::createProduct/updateProduct/deleteProduct`, `AdminCategoryService::create/update/delete`, and `AdminCacheService::refreshCatalog` keep calling `bump()` — only the constructor type changes from concrete to contract.
4. **Smoke scenarios keep measuring service-level query budgets** (≤8 queries for catalog list per `tests/Feature/PerformanceSmokeTest.php:25-37`). No HTTP rewrite.
5. **Frontend untouched.** `resources/js/api/catalog.ts`, composables, mappers, contracts — zero changes.
6. **Eloquent models stay shared.** `Product`, `ProductVariant`, `Category`, `Inventory` remain in `app/Models/*`; the relocated code imports them under the `App\Models\` legacy-bridge allowance.
7. **Guardrail extended, not weakened.** `ModuleBoundaryGuardrailTest` continues to pass; the migration proves the C0 boundary is load-bearing. New contracts are added under `App\Domains\Catalog\Contracts\*` and consumed only through that namespace.

## Out of scope (deliberate)

- **Admin catalog writes** (`AdminCatalogService`, `AdminCategoryService`, `AdminCacheService`, `AdminProductReadRepository`, `CategoryRepository`, admin handlers/controllers). They stay in their current namespaces; only their `CatalogVersionService` import becomes a contract import.
- **Eloquent model ownership.** Models stay in `app/Models/*`. Model-ownership wave is post-C7.
- **Reshaping `CatalogProductReadRepository` to return DTOs instead of `Product`.** Documented allowance; revisit at the model-ownership wave.
- **Throttle limiter `search`.** Registration (in `AppServiceProvider` or dedicated file) untouched.
- **`cache.headers` middleware.** Stays as-is.
- **Catalog categories read-path leakage** (`CatalogService::categories()` queries `Category::query()` directly instead of through a repository). Behavior-preserving move; flag for a later cleanup wave.
- **Frontend changes.** Zero.

## Implementation slices

### Slice 1 — Contract surface (no runtime behavior change)

Create the three contract files under `app/Domains/Catalog/Contracts/`:

- `CatalogProductReadRepository.php` — moved verbatim from `app/Application/Catalog/Contracts/CatalogProductReadRepository.php`, namespace `App\Domains\Catalog\Contracts`. Keeps `Product` and `LengthAwarePaginator` imports.
- `CatalogCacheVersion.php` — new interface, methods `current(): int` and `bump(): int`.
- `CatalogReadService.php` — new interface, methods `list(CatalogProductListFilterDto, int $perPage = 12): LengthAwarePaginator`, `productBySlug(string $slug): ?Product`, `categories(): Collection`. Mirrors the current `CatalogService` public surface.
- `Dto/CatalogProductListFilterDto.php` — moved verbatim from `app/Application/Catalog/Dto/CatalogProductListFilterDto.php`, namespace `App\Domains\Catalog\Contracts\Dto`.

No behavior change; contracts are not yet bound or implemented.

### Slice 2 — Implementation files moved (still no runtime wiring)

Move the implementation files into the module:

- `app/Domains/Catalog/Services/CatalogService.php` — moved from `app/Services/Catalog/CatalogService.php`. Now `implements CatalogReadService`. Namespace `App\Domains\Catalog\Services`.
- `app/Domains/Catalog/Services/CatalogVersionService.php` — moved from `app/Services/Catalog/CatalogVersionService.php`. Now `implements CatalogCacheVersion`. Namespace `App\Domains\Catalog\Services`.
- `app/Domains/Catalog/Repositories/CatalogProductReadRepository.php` — moved from `app/Repositories/CatalogProductReadRepository.php`. Now implements `App\Domains\Catalog\Contracts\CatalogProductReadRepository`. Namespace `App\Domains\Catalog\Repositories`.
- `app/Domains/Catalog/Application/Queries/{PaginateCatalogProductsHandler,GetCatalogProductBySlugHandler,ListCatalogCategoriesHandler}.php` + the two query payloads — moved from `app/Application/Catalog/Queries/*`. They now import `CatalogService` from the new namespace.
- `app/Domains/Catalog/Application/Dto/*` — 7 DTOs moved from `app/Application/Catalog/Dto/*` (every DTO except `CatalogProductListFilterDto` which lives in Contracts/Dto). Update their `App\Models\*` imports stay; namespace becomes `App\Domains\Catalog\Application\Dto`.
- `app/Domains/Catalog/Controllers/CatalogController.php` — moved from `app/Http/Controllers/Api/V1/CatalogController.php`. Now `final`. Imports updated to new handler namespaces.
- `app/Domains/Catalog/Controllers/CatalogIndexRequest.php` — moved from `app/Http/Requests/Catalog/CatalogIndexRequest.php`.

**Delete the original files only after the new ones compile and all references update** (Slice 3-5).

### Slice 3 — Wiring: routes, providers, admin imports, smoke scenarios

- `routes/api.php` — update `use App\Http\Controllers\Api\V1\CatalogController;` to `use App\Domains\Catalog\Controllers\CatalogController;`.
- `app/Providers/ApplicationBindingsServiceProvider.php` — remove the `CatalogProductReadRepository` binding (line 41) and the import (line 12); it moves to `CatalogServiceProvider`.
- `app/Domains/Catalog/CatalogServiceProvider.php` (new) — registers 3 bindings: `CatalogProductReadRepository` → implementation, `CatalogCacheVersion` → `CatalogVersionService`, `CatalogReadService` → `CatalogService`. Gated registration in `bootstrap/providers.php`.
- `bootstrap/providers.php` — add `App\Domains\Catalog\CatalogServiceProvider::class`.
- 3 admin services update their constructor type from `App\Services\Catalog\CatalogVersionService` to `App\Domains\Catalog\Contracts\CatalogCacheVersion`: `AdminCatalogService`, `AdminCategoryService`, `AdminCacheService`.
- 2 performance-smoke scenarios update their constructor type from `App\Services\Catalog\CatalogService` to `App\Domains\Catalog\Contracts\CatalogReadService`: `CatalogListColdPerformanceSmokeScenario`, `CatalogListWarmPerformanceSmokeScenario`.
- 2 performance-smoke infrastructure files update their `CatalogProductListFilterDto` import to the new contract namespace: `PerformanceSmokeSetupFactory`, `PerformanceSmokeContextDto`.
- `psalm.xml:49` — update the per-file suppression path from `app/Services/Catalog/CatalogService.php` to `app/Domains/Catalog/Services/CatalogService.php`.

### Slice 4 — Tests

- `tests/Feature/CatalogTest.php` — extract the SPA-shell cache test to `tests/Feature/SpaShellCacheTest.php`; keep catalog-only tests; update imports to the new namespace.
- `tests/Unit/CatalogVersionServiceTest.php` — update to resolve via `app(CatalogCacheVersion::class)`; keep `'catalog:version'` cache-key assertions.
- `tests/Unit/ApplicationRepositoryBindingTest.php` — update the catalog binding assertion to resolve from the new namespace.
- `tests/Unit/Architecture/RepositoryReadBoundaryTest.php` — update the forbidden-namespace string from `'App\\Application\\Catalog\\Dto\\'` to `'App\\Domains\\Catalog\\Application\\Dto\\'` (or remove the catalog-specific row if the new `ModuleBoundaryGuardrail` subsumes it).
- Add `tests/Feature/CatalogModuleRelocationTest.php` (new) — a small feature test asserting the 3 endpoints still respond identically post-move (effectively a smoke for the relocation itself; the heavier contract verification stays in `OpenApiConformanceFeatureTest`).
- `OpenApiConformanceFeatureTest` — no change needed (it asserts against HTTP paths only). Re-run to confirm green.

### Slice 5 — Module README + guardrail extension

- `app/Domains/Catalog/README.md` — replace the placeholder with the actual module documentation: active subfolders (`Contracts`, `Controllers`, `Application`, `Services`, `Repositories`); contract surface (`CatalogProductReadRepository`, `CatalogCacheVersion`, `CatalogReadService`, `Dto/CatalogProductListFilterDto`); operational contract (`catalog:version` cache key); explicit note that `Models/` is deferred to the post-C7 model-ownership wave.
- `ModularMonolithSkeletonGuardrailTest` — verify the README heading assertion (`# Catalog Domain Module`) still passes; update subfolder-name assertions if any.
- `ModuleBoundaryGuardrailTest` — confirm green with the new module content; the legacy-bridge allowlist shrinks implicitly (one concrete service class — `CatalogService` — is no longer in `App\Services\*`; admin services now reach it through `App\Domains\Catalog\Contracts\CatalogReadService`).

### Slice 6 — Docs sync

- `docs/REPO_MAP.md` — update the Catalog row in the per-module ownership table: migration state `complete (C1)`; list the actual contract surface.
- `docs/DOMAIN_MAP.md` — update the `### Catalog` migration-state marker to `[migration: complete C1]`; cross-reference the new contracts.
- `docs/ARCHITECTURE_REFACTOR_NEXT.md` — mark `C1` closed; append closed-block definition; update risk register (close any catalog-specific items); update exit target #26 (`C1` achieved); append change-control entry.
- `docs/REFACTORING_EXECUTION_PLAN.md` — append full C1 entry with verified baseline, slices, invariants, deterministic coverage, and verification steps.

### Slice 7 — Quality gate

Run the canonical sequence strictly in order, one command at a time:

1. `composer run lint`
2. `composer run analyse`
3. `php artisan test`
4. `npm run lint`
5. `npm run lint:ox`
6. `npm run format:ox:check`
7. `npm run type-check`
8. `npm run test`
9. `npm run build`

Plus the route-smoke requirement (controllers/middleware changed): `php artisan optimize:clear` and `php artisan route:list --path=api/v1/catalog`.

## Definition of done

1. Catalog read slice (transport + application + service + repository) lives under `app/Domains/Catalog/*` with namespace moves; old files removed.
2. `CatalogServiceProvider` registered in `bootstrap/providers.php`; binds the 3 contracts.
3. Admin services and performance-smoke scenarios consume the new contracts; no cross-module import of `App\Domains\Catalog\Services\*` or `App\Domains\Catalog\Application\*`.
4. `/api/v1/catalog/*` wire contract byte-identical (`OpenApiConformanceFeatureTest` green).
5. `catalog:version` cache key unchanged; admin writes still bump it.
6. `ModuleBoundaryGuardrailTest` green (legacy-bridge allowlist effectively shrinks by one concrete service).
7. Catalog README documents the active contract surface; skeleton guardrail still passes.
8. `docs/ARCHITECTURE_REFACTOR_NEXT.md` records `C1` as closed; `docs/REFACTORING_EXECUTION_PLAN.md` records the work and checks.
9. The quality gate is green and the executed checks are reported.
