# Catalog Domain Module

The Catalog module owns the public catalog read surface: paginated product
listing, product detail by slug, and active-category listing. It also owns
the catalog cache-version contract consumed by admin writes (cache
invalidation on every mutation) and by performance-smoke scenarios.

## Active subfolders

- `Contracts/` — module public API (interfaces + DTOs); see below.
- `Controllers/` — transport (`CatalogController`, `CatalogIndexRequest`).
- `Application/` — query handlers and DTOs (module-internal).
- `Services/` — `CatalogService` (read orchestration + cache), `CatalogVersionService` (cache versioning).
- `Repositories/` — `CatalogProductReadRepository` (persistence + query composition).
- `CatalogServiceProvider.php` — binds the Contracts to implementations; registered in `bootstrap/providers.php`.

The `Models/` subfolder is deferred to the post-C7 model-ownership wave.
Eloquent models (`Product`, `ProductVariant`, `Category`, `Inventory`) stay
shared in `app/Models/*` and are imported by this module under the C0
legacy-bridge allowance.

## Public contract surface

`App\Domains\Catalog\Contracts\`

- `CatalogProductReadRepository` — paginated catalog reads (`paginateCatalog`, `findActiveBySlug`). Returns `Product` at the boundary as a documented shared-kernel allowance pending the model-ownership wave.
- `CatalogCacheVersion` — cache-version contract (`current()`, `bump()`). Consumed by admin write services (`AdminCatalogService`, `AdminCategoryService`, `AdminCacheService`) to invalidate the catalog read cache on every mutation.
- `CatalogReadService` — read orchestration contract (`list`, `productBySlug`, `categories`). Consumed by the module's query handlers and by the performance-smoke scenarios that measure service-level query budgets.
- `Dto/CatalogProductListFilterDto` — typed filter DTO consumed by `PerformanceSmokeSetupFactory` and `PerformanceSmokeContextDto` to construct the smoke filter payload.

## Operational contract

- The cache key `catalog:version` is stable and shared between catalog reads (cache-key versioning) and admin writes (invalidation). The key string and the `current()`/`bump()` semantics must not change.
- The `/api/v1/catalog/*` HTTP contract is locked by `docs/api/openapi.yaml` (S1) and verified by `tests/Feature/OpenApiConformanceFeatureTest.php`.
- Relocation wired by `tests/Feature/CatalogModuleRelocationTest.php`.

## Migration state

Complete (Wave C1, `2026-07-05`). Admin catalog writes remain in
`app/Application/Admin/{Products,Categories}/*` and
`app/Services/Admin/Admin{Catalog,Category,Cache}Service.php`; they consume
this module only through the `CatalogCacheVersion` contract.
