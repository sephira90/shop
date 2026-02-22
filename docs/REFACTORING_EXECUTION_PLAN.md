# Refactoring Execution Plan

## Цель

Собрать устойчивую, масштабируемую и предсказуемую архитектуру (backend + frontend), сохранив текущий продуктовый функционал и API-контракты.

## Ключевые принципы

- Сначала корректность и безопасность, потом скорость.
- Единый API-контракт ответов и ошибок для всех `/api/v1/*`.
- Четкое разделение read/write-путей.
- Изоляция доменных модулей (Catalog, Cart, Checkout, Promotions, Fulfillment, IAM).
- Любой merge проходит production-ready quality gates.

## Приоритеты (по результатам аудита)

### P0

- Убрать hardcoded fake-gateway binding, перейти на config-driven provider strategy.
- Перенести dispatch side-effects на `afterCommit` / outbox-паттерн.
- Сделать атомарный `CatalogVersionService::bump()`.
- Усилить уникальность внешних идентификаторов для webhook-цепочек.

### P1

- Перевести account orders на server-driven filtering + pagination.
- Исправить профильные метрики (не только первая страница заказов).
- Довести API envelope до строгой единой формы (включая checkout).
- Унифицировать error pipeline (middleware + exception renderer + helpers).
- Выровнять webhook ingestion strategy (payment/shipping).

### P2

- Декомпозировать крупные composable/page в frontend.
- Вынести UI side-effects (`confirm`, `scrollTo`) из composable в view-layer.
- Ввести cleanup lifecycle для idempotency/webhook/carts.

## План реализации

## Phase 1: Correctness Foundation (3-5 дней)

### Scope

- Закрыть все P0.

### Tasks

- Ввести `payment.driver` и `shipping.driver` + фабрику провайдеров.
- Вынести побочные эффекты в `afterCommit` или outbox + worker.
- Переписать `CatalogVersionService::bump()` на атомарный инкремент.
- Добавить/уточнить индексы и уникальные ограничения для webhook identity.

### Exit Criteria

- Нет dispatch внутри незакоммиченной транзакции.
- Cache invalidation не теряется при параллельных write.
- Идентификация webhook-событий детерминирована.

## Phase 2: Contract & Flow Unification (4-6 дней)

### Scope

- Довести API до единообразного контракта.

### Tasks

- Привести `checkout/place-order` к единому envelope.
- Убрать разрозненные JSON errors в middleware/renderer.
- Обновить frontend data parsers под final shape.
- Добавить contract tests на критичные endpoint-ы.

### Exit Criteria

- 100% `/api/v1/*` возвращают единый shape.
- Нет endpoint-specific parsing hacks на frontend.

## Phase 3: Domain Scalability (1-2 недели)

### Scope

- Усилить доменные границы и поток данных.

### Tasks

- Ввести application-layer handlers (Command/Query) для Checkout/Orders/Promotions.
- Перевести account orders на server-side фильтры/поиск/статусы.
- Добавить read-model/summary endpoint для profile metrics.
- Стандартизировать webhook pipeline (ingest -> dedupe -> process -> observe).

### Exit Criteria

- Снижение связности сервисов и контроллеров.
- Предсказуемый high-load профиль по orders/webhooks.

## Phase 4: Frontend Structural Hardening (1-2 недели)

### Scope

- Финальная стабилизация frontend архитектуры.

### Tasks

- Разделить крупные composable на query/mutation/view-model уровни.
- Вынести UI-only эффекты из composable.
- Довести каталожную и account-пагинацию до server-driven UX.
- Добавить integration tests на ключевые сценарии UI-флоу.

### Exit Criteria

- Нет “god composables”.
- Стабильные сценарии при смене route/filter/page.

## Phase 5: Operations & Maintenance (3-4 дня)

### Scope

- Поддерживаемость production-потока.

### Tasks

- Добавить scheduled cleanup: idempotency records, webhook receipts, stale carts.
- Добавить алерты по SLO для API/webhooks/кэша.
- Зафиксировать runbook для инцидентов checkout/webhooks.

### Exit Criteria

- Контролируемый рост служебных таблиц.
- Прозрачный operational мониторинг.

## Quality Gates (обязательно после каждого набора изменений)

- `composer run lint`
- `composer run analyse`
- `php artisan test`
- `npm run lint`
- `npm run lint:ox`
- `npm run format:ox:check`
- `npm run type-check`
- `npm run test`
- `npm run build`
- `php artisan app:healthcheck`
- `php artisan app:performance-smoke`
- `php artisan app:webhook-flow-smoke`
- `php artisan app:api-contract-smoke`
- `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`

## Выполненный batch #1 (P0/P1)

1. Atomic cache version bump + tests.
2. `afterCommit` dispatch для payment/checkout side-effects.
3. Server-driven account orders filters + profile metrics endpoint.

## Progress Log

- `2026-02-21` — создан и принят execution-план рефакторинга (`97544ca`).
- `2026-02-21` — закрыт Batch #1:
- atomic `CatalogVersionService::bump()` + unit coverage (`148bdc5`);
- critical side-effects переведены на `afterCommit` (`37f1087`);
- account orders переведены на server-driven фильтрацию + summary endpoint (`b41ddce`).
- `2026-02-21` — усилены инженерные правила:
- зафиксировано требование коммитов по логическим блокам (`c64bfb7`);
- добавлены `oxlint`/`oxfmt` и включены в обязательные quality gates в локальных правилах, документации и CI (`ae5e993`);
- выполнено baseline-форматирование frontend-кода через `oxfmt` (`9056980`).
- `2026-02-21` — полный production-readiness quality gate прогнан в green:
- `composer run lint`
- `composer run analyse`
- `php artisan test`
- `npm run lint`
- `npm run lint:ox`
- `npm run format:ox:check`
- `npm run type-check`
- `npm run test`
- `npm run build`
- `php artisan app:healthcheck`
- `php artisan app:performance-smoke`
- `php artisan app:webhook-flow-smoke`
- `php artisan app:api-contract-smoke`
- `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
- `2026-02-21` — `Phase 1` provider strategy batch выполнен:
- добавлены config-driven драйверы `payment.driver` / `shipping.driver` (`config/payment.php`, `config/shipping.php`, `.env*.example`, `.env.testing`);
- `AppServiceProvider` переведен на резолвинг gateway-реализаций из config map с явной валидацией драйвера;
- в `PaymentService` и `ShippingService` удалены hardcoded provider/gateway значения, используется активный driver из конфига;
- добавлены unit-тесты резолвинга драйверов (`tests/Unit/GatewayDriverBindingTest.php`).
- `2026-02-21` — для provider strategy batch повторно прогнан полный production-readiness quality gate в green (все команды из секции `Quality Gates`).
- `2026-02-21` — `Phase 1` webhook identity constraints batch выполнен:
- добавлена additive миграция с уникальными ограничениями внешних идентификаторов:
  - `payments(gateway, transaction_id)` (`payments_gateway_transaction_unique`);
  - `shipments(provider, tracking_number)` (`shipments_provider_tracking_unique`);
- `ShippingService` обновлен на поиск shipment по `(provider, tracking_number)` для provider-safe webhook обработки;
- добавлены feature-тесты ограничений идентичности (`tests/Feature/WebhookIdentityConstraintTest.php`).
- `2026-02-21` — для webhook identity batch повторно прогнан полный production-readiness quality gate в green (все команды из секции `Quality Gates`).
- `2026-02-22` — `Phase 2` contract & flow unification batch выполнен:
- `checkout/place-order` приведен к единому `data`-envelope (убран top-level `payment`, платежный payload вложен в `data.payment`);
- `bootstrap/app.php` и `EnsureRoleMiddleware` переведены на единый error pipeline через `ApiResponse::error(...)`;
- убран неиспользуемый `ApiResponse::payload(...)`, в API остались единые response helpers (`data/paginated/error/deleted`);
- `app:api-contract-smoke` расширен проверкой success-контракта `checkout/place-order` (включая запрет top-level `payment`);
- обновлены тесты checkout-контракта (`tests/Feature/GuestCheckoutTest.php`) и contract smoke (`tests/Feature/ApiContractSmokeCommandTest.php`).
- `2026-02-22` — `Phase 2` frontend parser alignment batch выполнен:
- `placeCheckoutOrder` переведен на строгий mapper checkout-ответа (`resources/js/mappers/checkout.ts`) с обязательной проверкой `data.id`, `data.order_number`, `data.payment.*`;
- добавлены frontend contract tests для success-shape checkout ответа (`resources/js/tests/api/checkout-api.spec.ts`);
- добавлены frontend contract tests для error envelope parser (`resources/js/tests/api/response-error.spec.ts`).
- `2026-02-22` — `Phase 3` checkout application-layer bootstrap batch выполнен:
- добавлены command/query handlers для checkout-потока:
  - `PlaceCheckoutOrderHandler` (+ command/result DTO);
  - `PaginateMyOrdersHandler` (+ query DTO);
  - `GetMyOrdersSummaryHandler` (+ query DTO);
- `CheckoutController` переведен на application-layer orchestration (тонкий transport-слой без прямой доменной оркестрации).
- `2026-02-22` — `Phase 3` admin orders application-layer batch выполнен:
- добавлен модуль `app/Application/Admin/Orders` с command/query handlers:
  - `PaginateAdminOrdersHandler` (+ query DTO);
  - `GetAdminOrderDetailHandler` (+ query DTO);
  - `UpdateAdminOrderStatusHandler` (+ command DTO);
- `App\Http\Controllers\Api\V1\Admin\OrderController` переведен на application-layer orchestration без прямой зависимости от repository/service.
- `2026-02-22` — `Phase 3` admin promotions application-layer batch выполнен:
- добавлен модуль `app/Application/Admin/Promotions` с query/command handlers:
  - list: `PaginateAdminPromotionsHandler` (+ query DTO);
  - mutation flow: `Create/Update/DeleteAdminPromotionHandler` (+ command DTO);
  - coupons flow: `Create/UpdateAdminPromotionCouponHandler` (+ command DTO);
- `App\Http\Controllers\Api\V1\Admin\PromotionController` переведен на application-layer orchestration без прямой зависимости от repository/service.
- `2026-02-22` — `Phase 3` admin products application-layer batch выполнен:
- добавлен модуль `app/Application/Admin/Products` с query/command handlers:
  - list/detail: `PaginateAdminProductsHandler`, `GetAdminProductDetailHandler` (+ query DTO);
  - mutation flow: `Create/Update/DeleteAdminProductHandler` (+ command DTO);
- `App\Http\Controllers\Api\V1\Admin\ProductController` переведен на application-layer orchestration без прямой зависимости от repository/service.
- `2026-02-22` — `Phase 3` admin categories application-layer batch выполнен:
- добавлен модуль `app/Application/Admin/Categories` с query/command handlers:
  - list/detail: `PaginateAdminCategoriesHandler`, `GetAdminCategoryDetailHandler` (+ query DTO);
  - mutation flow: `Create/Update/DeleteAdminCategoryHandler` (+ command DTO);
- `App\Http\Controllers\Api\V1\Admin\CategoryController` переведен на application-layer orchestration без прямой зависимости от repository/service.
- `2026-02-22` — `Phase 3` finalization batch выполнен:
- зафиксирован архитектурный guardrail-тест `tests/Unit/Architecture/AdminControllerArchitectureTest.php`, проверяющий DI-границы admin-контроллеров (только `App\Application\Admin\...\*Handler`);
- добавлен итоговый ADR по application-layer conventions и DI boundaries:
  - `docs/adr/ADR-0001-admin-application-layer-conventions.md`;
  - индекс ADR: `docs/adr/README.md`.
- `2026-02-22` — `Phase 4` admin integration/UI-flow tests batch выполнен:
- добавлен integration test-suite `resources/js/tests/composables/use-admin-server-list-flows.spec.ts` для server-driven list flow:
  - `useAdminOrders`: debounce фильтров и перезагрузка с `page=1` + проверка server params;
  - `useAdminPromotions`: синхронизация search/status фильтров с перезагрузкой `page=1`;
  - `useAdminCategories`: синхронизация search/status фильтров и стабильный `per_page=200` contract.
- `2026-02-22` — `Phase 4` composable layering + UI-effects adapter batch выполнен:
- добавлен единый injectable контракт UI-сайдэффектов `resources/js/composables/admin/adminUiEffects.ts` (`confirm`, `scrollToTop`);
- `window.confirm`/`window.scrollTo` удалены из `useAdminProducts`, `useAdminPromotions`, `useAdminCategories`; browser-адаптер инжектируется из `AdminProductsPage.vue`, `AdminPromotionsPage.vue`, `AdminCategoriesPage.vue`;
- `useAdminPromotions` и `useAdminCategories` декомпозированы на query/mutation/view-model слои:
  - promotions: `resources/js/composables/admin/promotions/useAdminPromotionsQuery.ts`, `resources/js/composables/admin/promotions/useAdminPromotionsMutations.ts`, `resources/js/composables/admin/promotions/useAdminPromotionsViewModel.ts`;
  - categories: `resources/js/composables/admin/categories/useAdminCategoriesQuery.ts`, `resources/js/composables/admin/categories/useAdminCategoriesMutations.ts`, `resources/js/composables/admin/categories/useAdminCategoriesViewModel.ts`;
- добавлены composable-level tests для UI effects adapter integration:
  - `resources/js/tests/composables/admin-ui-effects-adapter.spec.ts`;
- для batch прогнаны quality gates в green:
  - `composer run lint`
  - `composer run analyse`
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
- `php artisan app:webhook-flow-smoke`
- `php artisan app:api-contract-smoke`
- `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
- `2026-02-22` — `Phase 4` admin products composable decomposition batch выполнен:
- `useAdminProducts` разделен на query/mutation/view-model слои с сохранением публичного API:
  - `resources/js/composables/admin/products/useAdminProductsQuery.ts`
  - `resources/js/composables/admin/products/useAdminProductsMutations.ts`
  - `resources/js/composables/admin/products/useAdminProductsViewModel.ts`
  - compatibility re-export: `resources/js/composables/admin/useAdminProducts.ts`;
- product-form category loading перенесен в query-layer (`loadCategories`) с сохранением server-driven пагинационного сбора и `per_page=200`;
- mutation/view-model слой сохранил текущий UX-контракт (`startEdit`, `removeProduct`, `toggleCatalogVisibility`, `refreshCatalogCache`, variant form helpers);
- для batch прогнаны quality gates в green:
  - `composer run lint`
  - `composer run analyse`
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
- `2026-02-22` — `Phase 4` admin orders composable decomposition batch выполнен:
- `useAdminOrders` разделен на query/mutation/view-model слои с сохранением публичного API:
  - `resources/js/composables/admin/orders/useAdminOrdersQuery.ts`
  - `resources/js/composables/admin/orders/useAdminOrdersMutations.ts`
  - `resources/js/composables/admin/orders/useAdminOrdersViewModel.ts`
  - compatibility re-export: `resources/js/composables/admin/useAdminOrders.ts`;
- query-слой выделяет server-driven list/detail + drafts/metrics (`filters`, `loadOrders`, `loadOrderDetail`, `currentDraft`, счетчики статусов);
- mutation-слой выделяет status update flow (`updateSelectedOrderStatus`) с синхронизацией detail/list моделей;
- view-model слой инкапсулирует notice/mutation orchestration и UI formatters (`formatPrice`, `formatAddress`, status class mappers);
- для batch прогнаны quality gates в green:
  - `composer run lint`
  - `composer run analyse`
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
- `2026-02-22` — `Phase 4` composable contracts + presentation helpers batch выполнен:
- вынесен общий mutation-контракт в `resources/js/composables/useAdminMutation.ts`:
  - экспортированы `ExecuteAdminMutationOptions<TResult>` и `ExecuteAdminMutation`;
  - дубли типов удалены из admin-модулей mutations/query:
    - `resources/js/composables/admin/categories/useAdminCategoriesMutations.ts`
    - `resources/js/composables/admin/promotions/useAdminPromotionsMutations.ts`
    - `resources/js/composables/admin/products/useAdminProductsMutations.ts`
    - `resources/js/composables/admin/orders/useAdminOrdersQuery.ts`
    - `resources/js/composables/admin/orders/useAdminOrdersMutations.ts`;
- выделены общие order/profile presentation-форматтеры:
  - `resources/js/utils/order-presentation.ts` (`formatMoney`, `formatOrderDate`, `formatOrderAddress`, `orderStatusClass`, `paymentStatusClass`, `shipmentStatusClass`);
- composable переключены на shared helpers:
  - `resources/js/composables/useAccountOrders.ts`
  - `resources/js/composables/useAccountProfile.ts`
  - `resources/js/composables/admin/orders/useAdminOrdersViewModel.ts`;
- добавлены unit tests для shared presentation helpers:
  - `resources/js/tests/utils/order-presentation.spec.ts`;
- для batch прогнаны quality gates в green:
  - `composer run lint`
  - `composer run analyse`
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
- `2026-02-22` — `Phase 4` account composables decomposition + tests batch выполнен:
- `useAccountOrders` разделен на query/view-model слои с сохранением публичного API:
  - `resources/js/composables/account/orders/useAccountOrdersQuery.ts`
  - `resources/js/composables/account/orders/useAccountOrdersViewModel.ts`
  - compatibility re-export: `resources/js/composables/useAccountOrders.ts`;
- в `useAccountOrdersQuery` добавлена опциональная инъекция `route/router` для тестируемости без DOM/router runtime coupling;
- добавлены composable-level tests для account-потока:
  - `resources/js/tests/composables/use-account-orders.spec.ts` (route-sync filters, pagination reload, details toggling);
  - `resources/js/tests/composables/use-account-profile.spec.ts` (profile load/update success+error, metrics loading);
- расширен frontend unit-coverage shared helpers и account-потока:
  - total frontend tests: `22 files / 70 tests` (green);
- для batch прогнаны quality gates в green:
  - `composer run lint`
  - `composer run analyse`
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
- `2026-02-22` — `Phase 4` admin mutation-flow stability tests batch выполнен:
- добавлен integration test-suite `resources/js/tests/composables/use-admin-mutation-flows.spec.ts`:
  - `products`: проверка стабильности page/filter после `submitProduct` (edit) и fallback до предыдущей страницы после `removeProduct` (delete last item on page);
  - `promotions`: проверка стабильности page/search/status после `submitPromotion` и fallback-page после `removePromotion`;
  - `categories`: проверка стабильности page/search/status/`per_page=200` после `submitCategory` и fallback-page после `removeCategory`;
  - покрыто `6` сценариев (`6` тестов, green).
- для batch прогнаны frontend quality gates в green:
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test -- use-admin-mutation-flows.spec.ts`
  - `npm run build`
- `2026-02-22` — `Phase 4` account profile composable decomposition batch выполнен:
- `useAccountProfile` декомпозирован на query/mutation/view-model слои с сохранением публичного API:
  - `resources/js/composables/account/profile/useAccountProfileQuery.ts`
  - `resources/js/composables/account/profile/useAccountProfileMutations.ts`
  - `resources/js/composables/account/profile/useAccountProfileViewModel.ts`
  - compatibility re-export: `resources/js/composables/useAccountProfile.ts`;
- сохранены текущие UI-контракты `AccountProfilePage` (`loadProfile`, `submitProfileUpdate`, `resetProfileForm`, profile computed fields, metrics, notice state);
- regression tests green:
  - `resources/js/tests/composables/use-account-profile.spec.ts`
  - `resources/js/tests/composables/use-admin-mutation-flows.spec.ts`;
- для batch прогнаны full production-readiness quality gates в green:
  - `composer run lint` (через `C:\composer\composer.bat run lint`)
  - `composer run analyse` (через `C:\composer\composer.bat run analyse`)
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
- `2026-02-22` — `Phase 4` route-synced server-list abstraction batch выполнен:
- добавлен общий route-query normalization helper:
  - `resources/js/queries/route-query.ts` (`toSingleQueryValue`, `normalizePageFromQuery`, `normalizeEnumQuery`);
- добавлен общий composable для route sync + pagination reload:
  - `resources/js/composables/useRouteSyncedPagination.ts`;
- общий abstraction применен в account/admin list flows:
  - `useAccountOrdersQuery` переведен на `useRouteSyncedPagination` с сохранением публичного API;
  - admin query composables получили route-sync интеграцию (опционально через `routeSync`):
    - `resources/js/composables/admin/orders/useAdminOrdersQuery.ts`
    - `resources/js/composables/admin/promotions/useAdminPromotionsQuery.ts`
    - `resources/js/composables/admin/categories/useAdminCategoriesQuery.ts`
    - `resources/js/composables/admin/products/useAdminProductsQuery.ts`;
  - добавлен shared type-контракт admin route-sync options:
    - `resources/js/composables/admin/adminRouteSync.ts`;
- admin pages переведены на URL-синхронизацию filters/page (route+router injection в view-model):
  - `resources/js/pages/admin/AdminOrdersPage.vue`
  - `resources/js/pages/admin/AdminPromotionsPage.vue`
  - `resources/js/pages/admin/AdminCategoriesPage.vue`
  - `resources/js/pages/admin/AdminProductsPage.vue`;
- расширены query-level и integration tests:
  - новый suite: `resources/js/tests/composables/use-route-synced-pagination.spec.ts`;
  - `resources/js/tests/composables/use-admin-server-list-flows.spec.ts` расширен route-sync сценариями для orders/promotions/categories;
  - `resources/js/tests/queries/admin/orders-query.spec.ts`, `resources/js/tests/queries/admin/promotions-query.spec.ts`, `resources/js/tests/queries/admin/categories-query.spec.ts`, `resources/js/tests/queries/admin/products-query.spec.ts` расширены parse/build/compare route-query assertions;
  - `resources/js/tests/composables/use-account-orders.spec.ts` обновлен под общий router-signature;
- для batch прогнаны full production-readiness quality gates в green:
  - `composer run lint` (через `C:\composer\composer.bat run lint`)
  - `composer run analyse` (через `C:\composer\composer.bat run analyse`)
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
- `2026-02-22` — `Phase 5` cleanup lifecycle + ops runbook batch выполнен:
- добавлен lifecycle cleanup command `app:maintenance-cleanup` с dry-run и retention overrides:
  - `app/Console/Commands/AppMaintenanceCleanupCommand.php`;
  - `config/cleanup.php`;
  - scheduler wiring: `routes/console.php`;
  - feature coverage: `tests/Feature/AppMaintenanceCleanupCommandTest.php`;
- добавлен config-driven SLO alert scheduling через `app:observability-report`:
  - `config/observability.php` (`alerts` блок: cron/window/thresholds/required samples);
  - scheduler wiring: `routes/console.php`;
  - feature coverage: `tests/Feature/ObservabilityReportCommandTest.php` (scheduler registration assertion);
  - env templates синхронизированы: `.env.example`, `.env.stage.example`, `.env.prod.example`, `.env.testing`;
- добавлен operational runbook для инцидентов checkout/webhooks:
  - `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md`;
  - README синхронизирован по командам и конфигам (`README.md`).
- для batch прогнаны full production-readiness quality gates в green:
  - `composer run lint` (через `C:\composer\composer.bat run lint`)
  - `composer run analyse` (через `C:\composer\composer.bat run analyse`)
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
- `2026-02-22` — `Phase 5` observability alert-routing batch выполнен:
- добавлен command-wrapper `app:observability-alert-check`:
  - запускает `app:observability-report` с config-driven SLO thresholds;
  - при `FAIL` маршрутизирует алерты по каналам и возвращает non-zero статус;
  - файлы: `app/Console/Commands/AppObservabilityAlertCheckCommand.php`, `routes/console.php`;
- добавлен alert routing service + email notification:
  - `app/Support/Observability/ObservabilityAlertRouter.php`;
  - `app/Notifications/ObservabilitySloFailureNotification.php`;
  - поддержаны каналы: `email`, `slack webhook`, `pagerduty events v2`;
  - добавлен cooldown suppression для защиты от alert storm;
- расширен observability alerts config и env templates:
  - `config/observability.php` (`alerts.email/slack/pagerduty`, `cooldown_minutes`);
  - `.env.example`, `.env.stage.example`, `.env.prod.example`, `.env.testing`;
- добавлено покрытие alert-routing и scheduler wiring:
  - `tests/Feature/ObservabilityAlertCheckCommandTest.php`;
  - `tests/Feature/ObservabilityReportCommandTest.php` (scheduler assertion обновлен на `app:observability-alert-check`);
- документация синхронизирована:
  - `README.md`
  - `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md`;
- для batch прогнаны full production-readiness quality gates в green:
  - `composer run lint` (через `C:\composer\composer.bat run lint`)
  - `composer run analyse` (через `C:\composer\composer.bat run analyse`)
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
  - `php artisan app:observability-alert-check`
- `2026-02-22` — `Phase 5` on-call drill сценарий + coverage batch выполнен:
- добавлен выделенный drill smoke orchestration command:
  - `app:oncall-drill-smoke` (`app/Console/Commands/AppOncallDrillSmokeCommand.php`);
  - dry-run checks: `app:healthcheck`, `app:observability-report` (alerts-threshold config), `app:maintenance-cleanup --dry-run`;
  - optional extended checks: `--with-write-smokes` (`app:api-contract-smoke`, `app:webhook-flow-smoke`) с поддержкой `--persist`;
  - встроена escalation matrix по check-кодам (`severity/owner/next_step`) в output;
- добавлено feature coverage drill-процесса:
  - `tests/Feature/OncallDrillSmokeCommandTest.php` (success/failure сценарии);
- runbook расширен tabletop процедурой и escalation matrix:
  - `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md`;
- README синхронизирован новой командой:
  - `README.md` (`php artisan app:oncall-drill-smoke`).
- для batch прогнаны full production-readiness quality gates в green:
  - `composer run lint` (через `C:\composer\composer.bat run lint`)
  - `composer run analyse` (через `C:\composer\composer.bat run analyse`)
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
  - `php artisan app:observability-alert-check`
  - `php artisan app:oncall-drill-smoke`
- `2026-02-22` — `Phase 5` final closeout выполнен:
- проверены и зафиксированы exit criteria `Phase 5`:
  - controlled growth служебных таблиц закрыт через cleanup lifecycle (`app:maintenance-cleanup`, scheduler, retention config, feature tests);
  - прозрачный operational мониторинг закрыт через SLO report + alert routing + on-call drill + incident runbook;
- оформлен release readiness checklist:
  - `docs/PHASE5_RELEASE_READINESS_CHECKLIST.md` (go/no-go, env/scheduler readiness, quality/smoke verification);
- `Phase 5` зафиксирован как завершенный, дальнейшие действия вынесены в post-closeout backlog.
- `2026-02-22` — `Phase 5` follow-up (scheduled on-call drill wiring) выполнен:
- принято решение и реализовано регулярное расписание `app:oncall-drill-smoke` с env-guard:
  - `config/oncall.php` (`enabled/cron/with_write_smokes/persist`);
  - scheduler wiring: `routes/console.php` (`withoutOverlapping`, config-driven command options);
  - env templates синхронизированы: `.env.example`, `.env.stage.example`, `.env.prod.example`, `.env.testing`;
- покрытие scheduler wiring добавлено в:
  - `tests/Feature/OncallDrillSmokeCommandTest.php` (`test_oncall_drill_command_is_registered_in_scheduler`);
- документация синхронизирована:
  - `README.md` (on-call drill schedule config variables);
  - `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md` (drill scheduling section);
  - `docs/PHASE5_RELEASE_READINESS_CHECKLIST.md` (scheduler readiness updated).
- для batch прогнаны full production-readiness quality gates в green:
  - `composer run lint` (через `C:\composer\composer.bat run lint`)
  - `composer run analyse` (через `C:\composer\composer.bat run analyse`)
  - `php artisan test`
  - `npm run lint`
  - `npm run lint:ox`
  - `npm run format:ox:check`
  - `npm run type-check`
  - `npm run test`
  - `npm run build`
  - `php artisan app:healthcheck`
  - `php artisan app:performance-smoke`
  - `php artisan app:webhook-flow-smoke`
  - `php artisan app:api-contract-smoke`
  - `php artisan app:observability-report --minutes=120 --max-api-slow-rate=0.30 --max-webhook-lag-warn-rate=0.30 --require-api-samples --require-webhook-samples`
  - `php artisan app:observability-alert-check`
  - `php artisan app:oncall-drill-smoke`

## Следующий batch

1. Разбить накопленные изменения на коммиты по логическим блокам и запушить.
2. Подготовить стартовый backlog следующего этапа (post-`Phase 5`) с приоритизацией по рискам production эксплуатации.
3. Зафиксировать финальный `Phase 5` summary в архитектурной документации (`docs/ARCHITECTURE_REFACTOR_PLAN.md`).
