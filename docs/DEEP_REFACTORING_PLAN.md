# Deep Refactoring Plan

> Historical plan (archived). Active architecture execution source-of-truth: `docs/ARCHITECTURE_REFACTOR_NEXT.md`.

## 1) Цель

Довести проект до архитектурно устойчивого состояния для масштабирования, где:

- слои строго изолированы (transport/application/domain/infrastructure),
- контракты API детерминированы и проверяются тестами,
- критические флоу (checkout/webhook/admin) надежны при конкуренции,
- observability отражает реальные runtime-сигналы без шума от smoke-сценариев.

## 2) Принципы выполнения

- Architecture-first: никакие временные хаки не допускаются.
- Изменения выполняются логическими блоками.
- На каждый блок: код + тесты + обновление планов.
- Quality gate запускается строго последовательно.

## 3) Приоритизированный roadmap

### Wave P0. Stabilization (обязательный фундамент)

#### P0.1 Frontend race-safety для серверных списков

- [x] Добавить request sequencing + cancellation в `useServerPaginatedList`.
- [x] Унифицировать стратегию защиты от out-of-order ответов во всех admin/account list флоу.
- [x] Добавить тест-кейсы на out-of-order, cancel, debounce boundary.

Definition of Done:

- Любой поздний ответ не может перезаписать более новый state.
- Есть регрессионные тесты на race-condition.

Status:

- Completed: 2026-02-23.

---

#### P0.2 Полная transport purity контроллеров API

- [x] Перенести orchestration из `Auth`, `Cart`, `Catalog`, `Checkout pay`, `Admin Cache` в application handlers.
- [x] Оставить в контроллерах: validate/authorize/handler/response.
- [x] Расширить архитектурные тесты с `AdminControllerArchitectureTest` на все API-контроллеры.

Definition of Done:

- Контроллеры не зависят от service/repository напрямую.
- Архитектурные тесты ломаются при любом layer leakage.

Status:

- Completed: 2026-02-23.

---

#### P0.3 Unified webhook processing pipeline

- [x] Вынести общий pipeline для webhook (receipt/dedupe/hash/lock/transition/outcome).
- [x] Оставить payment/shipping только в provider-specific адаптерах.
- [x] Стандартизировать outcome taxonomy и ошибки.

Definition of Done:

- Нет дублирования core webhook-процесса между payment/shipping.
- Одинаковая idempotency semantics на обоих провайдерах.

Status:

- Completed: 2026-02-23.

---

#### P0.4 Observability hygiene

- [x] Ввести `source` измерений (`runtime|smoke`) в observability payload.
- [x] Исключить `smoke` события из SLO thresholds/alerting.
- [x] Обновить observability report/alert router и соответствующие тесты.

Definition of Done:

- Smoke-запуски не искажают production SLO-картину.
- Отчеты и алерты учитывают только runtime (или явно фильтруются политикой).

Status:

- Completed: 2026-02-23.

### Wave P1. Architecture Strengthening

#### P1.1 Checkout transactional performance hardening

- [x] Укоротить transaction lock window в checkout.
- [x] Перейти на batch-подход к inventory lock/reserve.
- [x] Оптимизировать создание order items (где возможно bulk pattern).

Definition of Done:

- Снижение количества SQL round-trips и времени транзакции.
- Сохранена корректность stock/discount/idempotency.

Status:

- Completed: 2026-02-23.

---

#### P1.2 Shipment idempotency hardening

- [x] Зафиксировать инвариант «одна активная shipment-попытка на заказ».
- [x] Устранить риск дубликатов при конкурентных job retries.
- [x] Добавить конкурентные тесты на dispatch flow.

Definition of Done:

- Повторные/конкурентные запуски не создают дубли shipment.

Status:

- Completed: 2026-02-23.

---

#### P1.3 Strict API contract parsing на frontend

- [x] Ужесточить `normalizeListResponse` и `extractData`.
- [x] Убрать избыточные permissive ветки после выравнивания backend envelope.
- [x] Добавить контрактные тесты на отказ при неверной форме payload.

Definition of Done:

- Любой контрактный дрейф API быстро детектируется тестами.

Status:

- Completed: 2026-02-23.

---

#### P1.4 Page orchestration cleanup (frontend)

- [x] Вынести checkout/auth page business-flow в composables view-model слоя.
- [x] Инъектировать browser side effects через adapters.
- [x] Оставить `pages/*` только orchestration.

Definition of Done:

- Нет тяжелой доменной логики в page-компонентах.

Status:

- Completed: 2026-02-23.

---

#### P1.5 Repository query-shape deduplication

- [x] Унифицировать повторяющиеся select/with/filter блоки в `ProductRepository`.
- [x] Ввести локальные query builders/scopes для единого источника truth.

Definition of Done:

- Нет расхождений projection-конфигураций между list/show path.

Status:

- Completed: 2026-02-23.

### Wave P2. Scale & Operations

#### P2.1 Декомпозиция smoke-команд

- [x] Разбить крупные smoke-команды на scenario-классы.
- [x] Вынести общий API test client и общие assertion helpers.
- [x] Сократить размер command entrypoints.

Definition of Done:

- Команды компактны и легко расширяются новыми сценариями.

Status:

- Completed: 2026-02-23.

---

#### P2.2 Тестовая архитектура frontend

- [x] Разбить `ui-component-contracts.spec.ts` на доменные тестовые пакеты.
- [x] Вынести общие mount/helpers/fixtures.

Definition of Done:

- Меньший blast radius при падениях, ускоренный triage.

Status:

- Completed: 2026-02-23.

---

#### P2.3 Performance and ops budgets

- [x] Добавить budget checks для checkout/cart/admin list flows.
- [x] Включить regression checks в CI/quality pipeline.

Definition of Done:

- Деградации производительности выявляются автоматически до merge.

Status:

- Completed: 2026-02-23.

## 4) Порядок выполнения

1. Завершить весь P0.
2. Выполнить P1 в порядке 1.1 -> 1.5.
3. Выполнить P2.
4. После каждого завершенного логического блока:
   - обновить `docs/REFACTORING_EXECUTION_PLAN.md`,
   - указать выполненные изменения и прогнанные проверки,
   - сделать отдельный логичный commit.

## 5) Quality Gate (строго последовательно)

1. `composer run lint`
2. `composer run analyse`
3. `php artisan test`
4. `npm run lint`
5. `npm run lint:ox`
6. `npm run format:ox:check`
7. `npm run type-check`
8. `npm run test`
9. `npm run build`

## 6) Риски и контроль

- Риск регрессий в checkout/webhook при изменении транзакционной логики.
  - Контроль: feature tests + конкурентные тесты + smoke.
- Риск нарушения обратной совместимости API envelope.
  - Контроль: API contract tests на уровне backend и frontend parser.
- Риск скрытых race-condition на frontend.
  - Контроль: обязательные async тесты на out-of-order/cancel.

## 7) Критерий завершения программы

- Все пункты P0/P1/P2 закрыты.
- Architecture tests покрывают весь API слой.
- Quality gate зеленый.
- План выполнения (`REFACTORING_EXECUTION_PLAN`) актуализирован по всем блокам.

## 8) DTO Program (Incremental)

### DTO Wave 0. Foundation and guardrails

- [x] ADR strategy: `docs/adr/ADR-0002-dto-strategy.md`
- [x] Architecture allowlist: `tests/Support/Architecture/ArrayPayloadAllowlist.php`
- [x] Architecture tests:
  - `tests/Unit/Architecture/ApplicationDtoBoundaryTest.php`
  - `tests/Unit/Architecture/ServiceDtoBoundaryTest.php`
- [x] Frontend DTO baseline checks:
  - `resources/js/tests/api/dto-boundary.spec.ts`
  - `tests/Unit/Architecture/FrontendApiDtoContractPlanTest.md`
- [x] Baseline metrics: `docs/DTO_BASELINE_METRICS.md`

Status:

- Completed: 2026-02-24.

---

### DTO Wave 2. Admin categories/orders/promotions

- [x] Backend DTOs:
  - `CreateAdminCategoryInputDto`
  - `UpdateAdminCategoryInputDto`
  - `UpdateAdminOrderStatusInputDto`
  - `CreateAdminPromotionInputDto`
  - `UpdateAdminPromotionInputDto`
  - `CreateAdminPromotionCouponInputDto`
  - `UpdateAdminPromotionCouponInputDto`
- [x] Admin requests expose `toDto()`:
  - `CategoryStoreRequest`
  - `CategoryUpdateRequest`
  - `OrderStatusUpdateRequest`
  - `PromotionStoreRequest`
  - `PromotionUpdateRequest`
  - `CouponStoreRequest`
  - `CouponUpdateRequest`
- [x] Application/service migration:
  - admin categories/orders/promotions commands and handlers now carry typed DTOs;
  - `AdminCategoryService`, `AdminOrderService`, `AdminPromotionService`, `PromotionCouponSyncService` switched to typed DTO signatures.
- [x] Frontend admin typed contract pipeline:
  - `resources/js/contracts/api/v1/admin-categories.ts`
  - `resources/js/contracts/api/v1/admin-orders.ts`
  - `resources/js/contracts/api/v1/admin-promotions.ts`
  - `resources/js/contracts/api/v1/assertions/admin-categories.ts`
  - `resources/js/contracts/api/v1/assertions/admin-orders.ts`
  - `resources/js/contracts/api/v1/assertions/admin-promotions.ts`
  - `resources/js/mappers/admin/categories.ts`
  - `resources/js/mappers/admin/orders.ts`
  - `resources/js/mappers/admin/promotions.ts`
  - `resources/js/api/admin/categories.ts`
  - `resources/js/api/admin/orders.ts`
  - `resources/js/api/admin/promotions.ts`
- [x] Added frontend admin DTO contract tests:
  - `resources/js/tests/api/admin-contract.spec.ts`

Status:

- Completed: 2026-02-25.

---

### DTO Wave 1. Auth pilot

- [x] Backend auth DTOs:
  - `RegisterAuthInputDto`
  - `LoginAuthInputDto`
  - `UpdateAuthProfileInputDto`
  - `AuthUserDto`
  - `AuthTokenResultDto`
- [x] Auth requests now expose `toDto()`:
  - `RegisterRequest`
  - `LoginRequest`
  - `UpdateProfileRequest`
- [x] Auth application layer migrated from payload arrays to DTO contracts.
- [x] Auth controller returns DTO transport payload via `toArray()`.
- [x] Frontend auth typed contract pipeline:
  - `resources/js/contracts/api/v1/auth.ts`
  - `resources/js/contracts/api/v1/assertions/auth.ts`
  - `resources/js/mappers/auth.ts`
  - `resources/js/api/auth.ts`
  - `resources/js/stores/auth.ts`
- [x] Added frontend auth DTO contract tests:
  - `resources/js/tests/api/auth-contract.spec.ts`

Status:

- Completed: 2026-02-24.

---

### DTO Wave 3. Admin products DTO

- [x] Backend nested DTOs:
  - `CreateAdminProductInputDto`
  - `UpdateAdminProductInputDto`
  - `AdminProductVariantInputDto`
  - `AdminProductVariantInventoryInputDto`
- [x] `ProductStoreRequest` / `ProductUpdateRequest` expose `toDto()`.
- [x] Application/service migration:
  - `CreateAdminProductCommand` and `UpdateAdminProductCommand` store typed DTOs;
  - `CreateAdminProductHandler` and `UpdateAdminProductHandler` consume typed DTOs;
  - `AdminCatalogService` switched from array payload contracts to typed DTO signatures.
- [x] Frontend typed product contract pipeline:
  - `resources/js/contracts/api/v1/admin-products.ts`
  - `resources/js/contracts/api/v1/assertions/admin-products.ts`
  - `resources/js/mappers/admin/products.ts`
  - `resources/js/api/admin/products.ts`
  - `resources/js/types/admin-products.ts`
  - `resources/js/validators/admin/products.ts`
- [x] DTO contract tests updated:
  - `resources/js/tests/api/admin-contract.spec.ts`
  - `resources/js/tests/validators/admin/products-validator.spec.ts`

Status:

- Completed: 2026-02-27.

---

### DTO Wave 4. Cart + Checkout DTO

- [x] Backend DTOs:
  - `CartUpsertItemInputDto`
  - `RemoveCartItemInputDto`
  - `CartItemResultDto`
  - `CartSummaryResultDto`
  - `CartResultDto`
  - `CheckoutAddressInputDto`
  - `CheckoutPlaceOrderInputDto`
  - `CheckoutPaymentResultDto`
  - `CheckoutPlaceOrderResultDto`
- [x] Cart and checkout transport flow migrated to typed DTO:
  - `UpsertCartItemRequest` and `PlaceOrderRequest` expose `toDto()`;
  - cart handlers return `CartResultDto`;
  - `CartService::toResultDto(...)` replaces array payload transport;
  - `PlaceCheckoutOrderCommand` stores `CheckoutPlaceOrderInputDto`;
  - `PlaceCheckoutOrderHandler` returns `CheckoutPlaceOrderResultDto`;
  - `CheckoutService::placeOrder(...)` receives `CheckoutPlaceOrderInputDto`;
  - `CartController` and `CheckoutController` return DTO transport via `toArray()`.
- [x] Frontend typed cart/checkout contracts:
  - `resources/js/contracts/api/v1/cart.ts`
  - `resources/js/contracts/api/v1/checkout.ts`
  - `resources/js/contracts/api/v1/assertions/cart.ts`
  - `resources/js/contracts/api/v1/assertions/checkout.ts`
  - `resources/js/mappers/cart.ts`
  - `resources/js/mappers/checkout.ts`
  - `resources/js/api/cart.ts`
  - `resources/js/api/checkout.ts`
  - `resources/js/stores/cart.ts`
  - `resources/js/types/cart.ts`
  - `resources/js/types/checkout.ts`
- [x] DTO contract tests added/updated:
  - `resources/js/tests/api/cart-checkout-contract.spec.ts`
  - `resources/js/tests/api/checkout-api.spec.ts`
  - `resources/js/tests/cart-store.spec.ts`
  - `resources/js/tests/composables/use-checkout-page-view-model.spec.ts`

Status:

- Completed: 2026-02-27.

---

### DTO Wave 5. Catalog + Integration DTO

- [x] Catalog filter migration:
  - `PaginateCatalogProductsQuery` переведен на `CatalogProductListFilterDto`;
  - `CatalogService::list(...)` принимает typed `CatalogProductListFilterDto`;
  - `ProductRepository::paginateCatalog(...)` и filter application переведены на typed DTO;
  - `CatalogController` строит query через `CatalogProductListFilterDto::fromValidated(...)`.
- [x] DTO guardrails tightened for catalog:
  - `tests/Support/Architecture/ArrayPayloadAllowlist.php` обновлен:
    - `BASELINE_APPLICATION_ARRAY_PAYLOAD_COUNT` снижен до `0`;
    - `CatalogService` удален из service array payload allowlist.
- [x] Payment/Shipping result DTO migration:
  - добавлены `PaymentCreationResultDto` и `ShipmentCreationResultDto`;
  - `PaymentGatewayInterface::createPayment(...)` и `ShippingGatewayInterface::createShipment(...)` возвращают typed DTO;
  - `FakePaymentGateway`, `FakeShippingGateway` и `GatewayDriverBindingTest` переведены на typed result DTO;
  - `PaymentService` и `ShippingService` используют DTO поля вместо array shape.
- [x] Webhook typed payload adapters:
  - добавлены `PaymentWebhookPayloadDto` и `ShippingWebhookPayloadDto`;
  - `PaymentWebhookAdapter` и `ShippingWebhookAdapter` добавляют typed parse-step перед transition logic;
  - `WebhookProcessingPipeline` остается универсальным (adapter contract без breaking changes).

Status:

- Completed: 2026-02-27.

---

### DTO Wave 6. Frontend DTO completion + hard enforcement

- [x] Frontend typed parse/assertion coverage completed for remaining public domains:
  - `catalog` contracts/assertions and API wiring;
  - `account/orders` contracts/assertions and API wiring.
- [x] Mapper layer migrated to typed wire DTO inputs (no domain `unknown` in `resources/js/mappers/*`).
- [x] Contract tests expanded:
  - `resources/js/tests/api/catalog-account-contract.spec.ts`.
- [x] Unknown usage hard-enforced in domain API/mapper layers:
  - baseline in `resources/js/tests/api/dto-boundary.spec.ts` reduced to `5`;
  - `unknown` retained only in response transport parser and assertion modules.
- [x] Backend strict guardrails finalized:
  - `public array $payload|$filters` in `app/Application` = `0`;
  - `handle(): array` in application handlers = `0`;
  - `tests/Support/Architecture/ArrayPayloadAllowlist.php` application/service allowlists cleaned.
- [x] Service webhook/integration contracts migrated from raw array params to typed payload object:
  - shared `JsonPayload` value object introduced;
  - webhook pipeline/adapters/services use typed payload boundary instead of raw array params.

Status:

- Completed: 2026-02-27.
