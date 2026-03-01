# DTO Implementation Plan (Backend + Frontend, Incremental)

> Historical plan (completed; archived). Do not use as the active execution authority. Active architecture execution source-of-truth: `docs/ARCHITECTURE_REFACTOR_NEXT.md`.

## Кратко

Цель: убрать неструктурированные `array<string, mixed>` / `unknown` из бизнес-критичных путей и перейти на типизированные DTO на границах:

- `transport -> application -> service -> integration -> frontend API`

Ограничение: HTTP-контракты `/api/v1/*` не меняются по форме (`data/meta/error`), меняется только внутренняя типизация и строгость.

## Scope

- В scope:
  - DTO для `input/output/filter/result` на backend.
  - Typed API DTO contracts на frontend.
  - Эволюционная миграция волнами (без Big Bang).
- Вне scope:
  - Изменение JSON envelope.
  - Изменение route URL.
  - Изменение схем БД ради DTO.

## Архитектурный стандарт DTO

### Naming

- `*InputDto` — вход use-case.
- `*FilterDto` — list/query filters.
- `*ResultDto` — результаты handlers/services.
- `*PayloadDto` — integration/webhook payload wrappers.

### Placement

- Backend:
  - `app/Application/<Domain>/Dto/*`
  - `app/Services/<Domain>/Dto/*`
  - `app/Http/Requests/*` должны иметь `toDto(): ...InputDto`
- Frontend:
  - `resources/js/contracts/api/v1/<domain>.ts`
  - `resources/js/contracts/api/v1/assertions/<domain>.ts`

### Implementation rules

- DTO — `final readonly class`.
- Фабрики: `fromValidated(array $validated): self`.
- Нормализация в DTO factory (trim/cast/default), не в handler/service.
- `toArray()` только на transport/presentation boundary.

## Изменения интерфейсов

- Backend:
  - Commands/Queries переходят с `array` на typed DTO.
  - Services переходят с `array $payload|$filters` на typed DTO.
  - `PaymentGatewayInterface::createPayment(...)` возвращает `PaymentCreationResultDto`.
  - `ShippingGatewayInterface::createShipment(...)` возвращает `ShipmentCreationResultDto`.
  - `CatalogService::list(...)` принимает `CatalogProductListFilterDto`.
- Frontend:
  - API modules уходят от `extractData<unknown>` / `normalizeListResponse<unknown>` в доменных методах.
  - Для endpoint: wire DTO + runtime assertion + mapper в domain type.
  - `ProductMutationPayload.variants` должен быть typed массивом DTO, не `Array<Record<string, unknown>>`.
- HTTP API:
  - Без breaking changes.

## План по волнам

### Wave 0 — Foundation and guardrails

- [x] ADR strategy (`docs/adr/ADR-0002-dto-strategy.md`)
- [x] Architecture allowlist (`tests/Support/Architecture/ArrayPayloadAllowlist.php`)
- [x] Architecture tests:
  - `tests/Unit/Architecture/ApplicationDtoBoundaryTest.php`
  - `tests/Unit/Architecture/ServiceDtoBoundaryTest.php`
- [x] Frontend baseline checks:
  - `resources/js/tests/api/dto-boundary.spec.ts`
  - `tests/Unit/Architecture/FrontendApiDtoContractPlanTest.md`
- [x] Baseline metrics (`docs/DTO_BASELINE_METRICS.md`)

Status: Completed (`2026-02-24`).

---

### Wave 1 — Auth DTO pilot

- [x] Backend DTO:
  - `RegisterAuthInputDto`
  - `LoginAuthInputDto`
  - `UpdateAuthProfileInputDto`
  - `AuthUserDto`
  - `AuthTokenResultDto`
- [x] Requests с `toDto()`:
  - `RegisterRequest`
  - `LoginRequest`
  - `UpdateProfileRequest`
- [x] Auth commands/handlers/controller переведены на typed DTO flow
- [x] Frontend auth typed pipeline:
  - `resources/js/contracts/api/v1/auth.ts`
  - `resources/js/contracts/api/v1/assertions/auth.ts`
  - `resources/js/mappers/auth.ts`
  - `resources/js/api/auth.ts`
  - `resources/js/stores/auth.ts`
- [x] Тесты:
  - `tests/Feature/AuthFlowTest.php`
  - `tests/Feature/ProfileUpdateTest.php`
  - `resources/js/tests/api/auth-contract.spec.ts`
  - `resources/js/tests/composables/use-auth-page-view-model.spec.ts`

Status: Completed (`2026-02-24`).

---

### Wave 2 — Admin categories/orders/promotions DTO

- [x] Input DTO:
  - `CreateAdminCategoryInputDto`, `UpdateAdminCategoryInputDto`
  - `UpdateAdminOrderStatusInputDto`
  - `CreateAdminPromotionInputDto`, `UpdateAdminPromotionInputDto`
  - `CreateAdminPromotionCouponInputDto`, `UpdateAdminPromotionCouponInputDto`
- [x] Перевести:
  - `AdminCategoryService`, `AdminOrderService`, `AdminPromotionService`, `PromotionCouponSyncService`
  - commands/handlers на DTO signatures
- [x] FormRequest `toDto()` для admin mutation requests
- [x] Frontend typed wire DTO contracts для admin categories/orders/promotions
- [x] Обновить тесты:
  - `AdminCategoryCrudTest`
  - `AdminPromotionCouponFlowTest`
  - `AdminPromotionValidationTest`
  - `AdminOrderSummaryContractTest`
  - `resources/js/tests/queries/admin/*` + relevant composables
  - `resources/js/tests/api/admin-contract.spec.ts`

Status: Completed (`2026-02-25`).

---

### Wave 3 — Admin products DTO

- [x] Nested DTO:
  - `CreateAdminProductInputDto`, `UpdateAdminProductInputDto`
  - `AdminProductVariantInputDto`, `AdminProductVariantInventoryInputDto`
- [x] Перевести `AdminCatalogService` на typed variant DTO
- [x] Frontend:
  - `AdminProductMutationRequestDto`
  - typed variant request DTO
  - `validators/admin/products.ts` без `Record<string, unknown>`
- [x] Тесты:
  - `tests/Feature/AdminProductVariantsTest.php`
  - `resources/js/tests/validators/admin/products-validator.spec.ts`
  - admin products query/composable/component contracts

Status: Completed (`2026-02-27`).

---

### Wave 4 — Cart + Checkout DTO

- [x] DTO:
  - `CartUpsertItemInputDto`, `RemoveCartItemInputDto`
  - `CheckoutPlaceOrderInputDto`, `CheckoutAddressInputDto`
  - `CartResultDto`, `CheckoutPlaceOrderResultDto`, `CheckoutPaymentResultDto`
- [x] Перевести:
  - `CartController`, `CheckoutController` на `toDto()`
  - `CartService::payload()` -> `CartResultDto`
  - `CheckoutService::placeOrder(..., CheckoutPlaceOrderInputDto ...)`
  - `PlaceCheckoutOrderCommand` хранит DTO
- [x] Frontend typed checkout/cart wire DTO contracts
- [x] Тесты:
  - `CartCheckoutTest`
  - `GuestCheckoutTest`
  - `CheckoutAuthenticatedTokenTest`
  - `CouponCheckoutTest`
  - frontend checkout api + composables

Status: Completed (`2026-02-27`).

---

### Wave 5 — Catalog + Integration DTO

- [x] Catalog:
  - `PaginateCatalogProductsQuery(array $filters)` -> `CatalogProductListFilterDto`
  - `CatalogService::list(CatalogProductListFilterDto $filter, int $perPage)`
- [x] Payment/Shipping:
  - `PaymentCreationResultDto`, `ShipmentCreationResultDto`
  - gateway interfaces/fake drivers возвращают DTO
- [x] Webhooks:
  - parse-step в typed payload DTO:
    - `PaymentWebhookPayloadDto`
    - `ShippingWebhookPayloadDto`
  - pipeline остается универсальным
- [x] Тесты:
  - `PaymentWebhookTest`
  - `ShippingWebhookTest`
  - `WebhookIdentityConstraintTest`
  - `WebhookFlowSmokeCommandTest`
  - `ApiContractSmokeCommandTest`
  - `PerformanceSmokeTest`

Status: Completed (`2026-02-27`).

---

### Wave 6 — Frontend DTO completion + hard enforcement

- [x] Endpoint-specific typed parse functions для всех `resources/js/api/*`
- [x] `unknown` usage ограничить:
  - только `resources/js/api/response.ts`
  - и `resources/js/contracts/api/v1/assertions/*`
- [x] Все `resources/js/mappers/*` принимают typed DTO input
- [x] Добавить TS contract tests для доменов:
  - `auth`, `catalog`, `cart`, `checkout`, `admin/*`, `account/orders`
- [x] Удалить legacy allowlist и включить strict guardrails:
  - `public array $payload|$filters` = `0`
  - `handle(): array` в application handlers = `0` (кроме явно задокументированных исключений)

Status: Completed (`2026-02-27`).

## Quality gates (после каждой волны, строго последовательно)

1. `composer run lint`
2. `composer run analyse`
3. `php artisan test`
4. `npm run lint`
5. `npm run lint:ox`
6. `npm run format:ox:check`
7. `npm run type-check`
8. `npm run test`
9. `npm run build`
10. При изменении routes/controllers:
  - `php artisan optimize:clear`
  - `php artisan route:list --path=api/v1/admin/promotions`

## Критерии завершения DTO программы

1. Все mutation/query entrypoints используют typed DTO.
2. В application/service слоях нет raw array payload как основного контракта.
3. Frontend API/mappers типизированы endpoint DTO без `unknown` в доменной логике.
4. API envelope и поведение не регресснули (feature + contract + smoke green).
5. Architecture tests блокируют возврат к array-based контрактам.

## Assumptions

- Scope: `Backend + Frontend`
- Rollout: `Incremental waves`
- DTO style: `Native readonly DTO`
- API JSON shape не меняется
- Валидация остается в FormRequest
- Временная совместимость только через явный allowlist с дедлайном удаления
