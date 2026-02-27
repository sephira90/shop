# Architecture Refactor Plan

> Historical plan (archived). Active architecture execution source-of-truth: `docs/ARCHITECTURE_REFACTOR_NEXT.md`.

## Goal

Построить масштабируемую, предсказуемую и безопасную архитектуру monolith-first для backend (Laravel) и frontend (Vue/TS), сохранив текущий продуктовый флоу и API-контракты в контролируемом режиме.

## Principles

- Безопасность и корректность важнее скорости добавления фич.
- Backward compatibility по API до завершения миграции клиентов.
- Единый контракт ошибок и ответов для всех API-эндпоинтов.
- Четкое разделение read/write-path и модульные границы доменов.
- Любое изменение проходит quality gates перед merge.

## Baseline Findings (Audit)

### P0

- Витрина может вернуть не опубликованный товар по `slug`.
- Не везде учитывается `is_active` у вариантов товара, корзина не валидирует активность SKU при добавлении.
- При админском изменении статуса заказа может стираться `cancelled_at`.

### P1

- Merge гостевой корзины в пользовательскую не обернут в транзакцию.
- Непоследовательная авторизация в админ-контроллерах (policy используется не везде явно).
- `FormRequest::authorize()` в админских request классах возвращает `true` без доменной проверки.

### P2

- Смешанный API-контракт (часть через `ApiResponse`, часть через ручные payload).
- Тяжелые eager-load в списках заказов без разделения summary/detail.
- Кэш каталога хранит paginator-объекты целиком.
- Повторяющаяся логика version bump для инвалидации каталожного кэша.
- Неунифицированный пайплайн webhook (payment/shipping).
- Frontend: eager imports роутов, обход общего data layer в публичных страницах, дубли парсинга ошибок.
- Слабый unit-слой тестов backend-доменов.

## Execution Plan

## Phase 0: Governance and Guardrails (1 day)

### Scope

- Зафиксировать целевой API envelope и error format.
- Зафиксировать Definition of Done для архитектурных PR.
- Утвердить ADR-шаблон (architecture decision record) для спорных решений.

### Deliverables

- Документ API contract v1.1 (совместимый режим).
- Чеклист архитектурного PR в `docs/`.
- ADR-шаблон и первый ADR по API-контракту.

### Exit Criteria

- Команда использует один формат ответа/ошибок как целевой.

## Phase 1: Correctness and Security Stabilization (2-4 days)

### Scope

- Закрыть все `P0` и критичные `P1`.
- Привести авторизацию к единому стандарту: policy + request authorize.
- Гарантировать транзакционность merge cart.

### Tasks

- Добавить фильтры `published_at` и `is_active` в storefront query-path.
- Добавить серверную валидацию SKU доступности в `CartService`.
- Исправить логику `cancelled_at` (immutable once cancelled, если бизнес-правило не требует обратного).
- Обернуть merge корзины в транзакцию + idempotent-safe update.
- Выравнять authorize flow в admin controllers/requests.

### Exit Criteria

- Нет возможности заказать неактивный/непубликованный товар.
- Критичные сценарии покрыты feature + unit тестами.

## Phase 2: API Contract Unification (3-5 days)

### Scope

- Полный переход контроллеров на единый `ApiResponse`-стиль.
- Единый объект ошибок и корреляции (`request_id`).

### Tasks

- Мигрировать публичные контроллеры на унифицированный envelope.
- Вынести маппинг исключений в единый слой.
- Добавить contract tests для ключевых endpoint-ов.

### Exit Criteria

- Все `/api/v1/*` возвращают единообразный envelope и error shape.
- Frontend не содержит endpoint-specific костылей для распаковки ответа.

## Phase 3: Backend Scalability (1-2 weeks)

### Scope

- Укрепить архитектуру доменных модулей и read/write границы.
- Упростить тяжелые запросы и нормализовать кэш-стратегию.

### Tasks

- Разделить `Order list summary` и `Order detail` по разным query-путям.
- Ввести query DTO/filter objects вместо raw arrays для сложных фильтров.
- Централизовать catalog cache invalidation (`CatalogVersionService`).
- Перевести webhook ingestion в унифицированный async pipeline с идемпотентностью.
- Добавить observability hooks: latency, cache hit ratio, webhook lag.

### Exit Criteria

- Средний размер ответа списка заказов снижен, p95 latency стабилен.
- Инвалидация кэша имеет единый код-путь.

## Phase 4: Frontend Scalability (1-2 weeks)

### Scope

- Единый frontend data-layer для public/account/admin зон.
- Повышение производительности загрузки и устойчивости UI.

### Tasks

- Перевести public/account страницы на `api + mappers + queries + composables + validators`.
- Включить route-level lazy loading для страниц.
- Централизовать error handling через shared parser/composable.
- Убрать stale состояния при смене route params (watchers, query keys).
- Выравнять типы DTO между backend resources и frontend types.

### Exit Criteria

- Нет дублирующейся логики API-парсинга в страницах.
- Initial bundle уменьшен, FCP/TTI улучшаются по сравнению с baseline.

## Phase 5: Quality System and CI Hardening (3-5 days)

### Scope

- Поднять минимальную инженерную планку и регрессионную защищенность.

### Tasks

- Добавить unit tests для сервисов и policy/authorization rules.
- Добавить integration tests для checkout/webhook/idempotency цепочек.
- Зафиксировать performance smoke-tests для критичных query-path.
- Сделать quality gates blocking в CI: `composer run lint`, `composer run analyse`, `php artisan test`, `npm run lint`, `npm run lint:ox`, `npm run format:ox:check`, `npm run type-check`, `npm run test`, `npm run build`.

### Exit Criteria

- Нельзя смержить PR с failing quality gates.
- Есть стабильный regression safety net для core flows.

## Workstream Ownership

- Backend Core: API contract, services, repositories, policies, webhooks.
- Frontend Core: data-layer, routing, composables, type alignment.
- QA/Platform: CI gates, test strategy, observability baseline.

## KPI and SLO Targets

- `0` критичных инцидентов по невалидным заказам (inactive/unpublished SKU).
- p95 для `catalog list` и `orders list` не деградирует после рефакторинга.
- `>= 80%` покрытие ключевых доменных сервисов unit+feature тестами.
- `100%` API endpoints на унифицированном contract.
- `100%` PR проходят production-readiness checks.

## Delivery Cadence

- Спринт 1: Phase 0 + Phase 1 + старт Phase 2.
- Спринт 2: завершение Phase 2 + основная часть Phase 3.
- Спринт 3: завершение Phase 3 + Phase 4.
- Спринт 4: Phase 5 + стабилизация + финальный hardening.

## Risk Register

- Риск: поломка фронта при унификации API.
- Митигация: dual-mode responses и контрактные тесты на переходный период.

- Риск: рост времени разработки из-за большого объема тестов.
- Митигация: приоритизация по критичности доменов (checkout, cart, promotions, orders).

- Риск: переработка без measurable эффекта.
- Митигация: baseline метрики до начала каждого phase и compare-after.

## Working Agreement

- Любой архитектурный PR содержит: цель, границы, риски, тест-план, rollback-план.
- Любая оптимизация производительности подтверждается метриками до/после.
- В спорных решениях сначала ADR, потом реализация.

## Immediate Next Actions

- Принять этот план как source of truth.
- Запустить реализацию с `Phase 1` (P0/P1 fixes) как первый execution batch.
- После Phase 1 обновить baseline и скорректировать объем `Phase 2`.

## Progress Log

- `2026-02-21`: `Phase 1` выполнен.
- Закрыты критичные `P0/P1` по storefront visibility, SKU availability, cart merge transaction, order cancellation consistency и admin authorization flow.
- Добавлены регрессионные тесты (`tests/Feature/PhaseOneHardeningTest.php`).

- `2026-02-21`: старт `Phase 2` (batch 1) выполнен.
- Унифицированы ответы через `ApiResponse` в `CatalogController`, `CartController`, `CheckoutController`, `AuthController`.
- В `ApiResponse` добавлены стандартный `error`-envelope и `per_page` в `paginated meta`.
- В error-контракт добавлен `request_id` (`X-Correlation-Id`) для трассировки.

- `2026-02-21`: `Phase 2` (batch 2) выполнен.
- На `ApiResponse` переведены `PasswordController`, `VerificationController`, `PaymentWebhookController`, `ShippingWebhookController`, `Admin\\CacheController`.
- Ручные `response()->json(...)` в `app/Http/Controllers/Api/V1/*` устранены; API слой приведен к единому response helper.

- `2026-02-21`: `Phase 2` (batch 3, frontend alignment) выполнен.
- Добавлен единый frontend parser для error envelope/validation/request id в `resources/js/api/response.ts` и подключен через `resources/js/composables/useApiError.ts`.
- Обновлены `AuthPage`, `CheckoutPage`, `AccountProfilePage`, `AccountOrdersPage` на общий parsing слой без локальных дублирующихся обработчиков.
- Пагинационный контракт фронта обновлен под `per_page` (`resources/js/types/pagination.ts`, `resources/js/composables/usePaginationMeta.ts`).

- `2026-02-21`: `Phase 3` (batch 1, admin orders read-path split) выполнен.
- Для admin orders реализован `summary/detail` split: список возвращает облегченный payload (`OrderSummaryResource`), детали загружаются отдельным запросом (`/api/v1/admin/orders/{id}`).
- В фронтенде админки добавлен lazy detail loading для заказов (`useAdminOrders`, `AdminOrdersPage`) с кэшированием деталей по `order_id`.
- Для пользовательского списка заказов убраны лишние eager-load (`payments`, `shipments`) и исключены лишние lazy-query в `OrderResource`.
- Добавлен feature-test контракта `tests/Feature/AdminOrderSummaryContractTest.php`.

- `2026-02-21`: `Phase 3` (batch 2, catalog cache invalidation centralization) выполнен.
- Добавлен единый `CatalogVersionService` (`app/Services/Catalog/CatalogVersionService.php`) с `current()` и `bump()` как единый код-путь для версии каталога.
- На `CatalogVersionService` переведены `AdminCatalogService`, `AdminCategoryService`, `AdminCacheService` и `CatalogService`; дубли `Cache::get/forever` логики удалены.
- Добавлен unit-тест сервиса версии каталога: `tests/Unit/CatalogVersionServiceTest.php`.

- `2026-02-21`: `Phase 3` (batch 3, typed admin list filters) выполнен.
- Для `orders/products/promotions` добавлены typed filter value objects и index request-классы (`app/Filters/Admin/*`, `app/Http/Requests/Admin/*IndexRequest.php`).
- Списки админки переведены на репозиторный query-path с filter DTO (`OrderRepository`, `ProductRepository`, новый `PromotionRepository`), контроллеры больше не собирают list-query вручную.
- Frontend list API/composables синхронизированы с серверными query filters (`resources/js/api/admin/*.ts`, `resources/js/composables/admin/*`), добавлен автоматический debounce reload при изменении фильтров.
- Добавлены feature-тесты фильтрации и валидации: `tests/Feature/AdminListFilteringTest.php`.

- `2026-02-21`: `Phase 4` (batch 1, admin server-driven filtering contract) выполнен.
- Убрана дублирующая runtime-фильтрация в admin composables; серверный list API стал единственным source of truth для search/status фильтров.
- Query-layer `resources/js/queries/admin/*` переведен на сборку query params (`build*ListParams`) вместо локальной фильтрации массивов.
- Добавлены frontend contract tests для list params builders: `resources/js/tests/queries/admin/orders-query.spec.ts`, `resources/js/tests/queries/admin/products-query.spec.ts`, `resources/js/tests/queries/admin/promotions-query.spec.ts`.

- `2026-02-21`: `Phase 4` (batch 2, shared server filters orchestration) выполнен.
- Добавлен общий composable `resources/js/composables/useServerListFilters.ts` для debounce/reload логики server-driven list фильтров.
- На общий composable переведены `useAdminOrders`, `useAdminProducts`, `useAdminPromotions`; локальные таймеры и дубли `watch + clearTimeout` удалены.

- `2026-02-21`: `Phase 4` (batch 3, shared paginated list abstraction) выполнен.
- Добавлен общий composable `resources/js/composables/useServerPaginatedList.ts` для унификации `items/page/meta/isLoading/load` логики server-driven списков.
- На `useServerPaginatedList` переведены `useAdminOrders`, `useAdminProducts`, `useAdminPromotions`; дубли `try/catch + applyMeta + page sync` удалены.
- Добавлены unit-тесты для общего composable: `resources/js/tests/composables/use-server-paginated-list.spec.ts`.

- `2026-02-21`: `Phase 4` (batch 4, unified admin notice handling) выполнен.
- Добавлен общий composable `resources/js/composables/useAdminNotice.ts` для унификации `notice` состояния и API-error mapping.
- На `useAdminNotice` переведены `useAdminOrders`, `useAdminProducts`, `useAdminPromotions`, `useAdminCategories`; дубли `notice.type/message` и `parseApiError(...)` удалены.
- Добавлены unit-тесты composable: `resources/js/tests/composables/use-admin-notice.spec.ts`.

- `2026-02-21`: `Phase 4` (batch 5, admin categories server-driven parity) выполнен.
- Для админских категорий добавлены typed list filters на backend (`AdminCategoryListFilter`, `CategoryIndexRequest`, `CategoryRepository`), `CategoryController@index` переведен на repository query-path.
- Frontend категорий выровнен с общей архитектурой (`buildAdminCategoryListParams`, `useServerPaginatedList`, server-driven search/status filters в `useAdminCategories` и `AdminCategoriesPage`).
- Добавлены контрактные тесты query-builder: `resources/js/tests/queries/admin/categories-query.spec.ts`, и feature-тесты backend фильтрации категорий в `tests/Feature/AdminListFilteringTest.php`.

- `2026-02-21`: `Phase 4` (batch 6, route-level lazy loading) выполнен.
- Роутер переведен с eager imports на route-level lazy loading для всех страниц (`resources/js/router/index.ts`), экспортирован `appRoutes` как тестируемый контракт.
- Добавлен frontend контрактный тест роут-конфига: `resources/js/tests/router/routes.spec.ts` (проверка lazy components и auth meta для защищенных маршрутов).

- `2026-02-21`: `Phase 4` (batch 7, storefront data-layer + stale-state hardening) выполнен.
- Публичные страницы каталога и товара переведены на единый data-layer (`resources/js/types/catalog.ts`, `resources/js/mappers/catalog.ts`, `resources/js/api/catalog.ts`, `resources/js/queries/catalog.ts`, `resources/js/composables/useCatalogProducts.ts`, `resources/js/composables/useCatalogProduct.ts`).
- Устранены stale-state сценарии при смене route query/params: каталог синхронизирует фильтры через URL query, товар перезагружается при смене `slug`, оба composable защищены от race-condition по request id.
- Checkout вынесен на отдельный API/validator слой (`resources/js/api/checkout.ts`, `resources/js/types/checkout.ts`, `resources/js/validators/checkout.ts`) и синхронизирует email/guest token реактивно.
- Добавлены frontend contract tests: `resources/js/tests/queries/catalog-query.spec.ts`, `resources/js/tests/validators/checkout-validator.spec.ts`.

- `2026-02-21`: `Phase 4` (batch 8, account pages data-layer alignment) выполнен.
- Страница заказов аккаунта переведена на единый слой `types + mappers + api + queries + composable` (`resources/js/types/account-orders.ts`, `resources/js/mappers/account/orders.ts`, `resources/js/api/account/orders.ts`, `resources/js/queries/account-orders.ts`, `resources/js/composables/useAccountOrders.ts`) с route-query синхронизацией фильтров/страницы.
- Страница профиля аккаунта переведена на composable-подход (`resources/js/composables/useAccountProfile.ts`), прямые HTTP-вызовы из страниц убраны.
- Добавлены frontend contract tests query-layer: `resources/js/tests/queries/account-orders-query.spec.ts`.

- `2026-02-21`: `Phase 5` (batch 1, CI quality gates hardening) выполнен.
- Усилен GitHub Actions workflow `.github/workflows/ci.yml`: workflow переименован в `Quality Gate`, добавлены `concurrency`, `permissions`, `timeout`, единый job `Full Quality Gate`.
- В CI зафиксирован полный blocking pipeline (`composer run lint/analyse`, `php artisan test`, `npm run lint/lint:ox/format:ox:check/type-check/test/build`) и production smoke-checks (`php artisan migrate --force`, `php artisan optimize:clear`, `php artisan route:list --path=api/v1/admin/promotions`, `php artisan app:healthcheck`).
- Обновлена документация по обязательному статус-чеку branch protection: `README.md`.

- `2026-02-21`: `Phase 5` (batch 2, authorization unit safety net) выполнен.
- Добавлен unit test-модуль матрицы прав для admin policies: `tests/Unit/Policies/AdminPolicyMatrixTest.php`.
- Покрыты role/ownership сценарии для `ProductPolicy`, `CategoryPolicy`, `PromotionPolicy`, `CouponPolicy`, `OrderPolicy` (admin/manager/customer и owner vs non-owner на заказах).

- `2026-02-21`: `Phase 5` (batch 3, performance smoke guards) выполнен.
- Добавлена production smoke-команда `app:performance-smoke` с budget-check для критичных query-path: `catalog list (cold/warm)` и `admin orders summary` (`app/Console/Commands/AppPerformanceSmokeCommand.php`).
- Добавлены feature smoke-тесты query budget для API-path: `tests/Feature/PerformanceSmokeTest.php`.
- CI quality gate расширен шагом `php artisan app:performance-smoke` и документация обновлена (`.github/workflows/ci.yml`, `README.md`, `composer.json`).

- `2026-02-21`: `Phase 5` (batch 4, webhook integration smoke hardening) выполнен.
- Добавлена production smoke-команда `app:webhook-flow-smoke` для end-to-end цепочки `checkout -> payment webhook -> shipment creation -> shipping webhook` с idempotency replay-check (`app/Console/Commands/AppWebhookFlowSmokeCommand.php`).
- Добавлен feature-тест smoke-команды: `tests/Feature/WebhookFlowSmokeCommandTest.php`.
- CI quality gate расширен шагом `php artisan app:webhook-flow-smoke`, документация/ops scripts синхронизированы (`.github/workflows/ci.yml`, `README.md`, `composer.json`).

- `2026-02-21`: `Phase 5` (batch 5, API contract smoke guards) выполнен.
- Добавлена production smoke-команда `app:api-contract-smoke` для проверки unified API envelope (`data/error/meta`) на ключевых `/api/v1` endpoint-ах, включая admin-path под Sanctum (`app/Console/Commands/AppApiContractSmokeCommand.php`).
- Добавлен feature-тест smoke-команды: `tests/Feature/ApiContractSmokeCommandTest.php`.
- CI quality gate расширен шагом `php artisan app:api-contract-smoke`, документация и ops scripts синхронизированы (`.github/workflows/ci.yml`, `README.md`, `composer.json`).

- `2026-02-21`: `Phase 3` (batch 4, observability baseline hooks) выполнен.
- Добавлен observability слой (`config/observability.php`, `app/Support/Observability/ObservabilityService.php`) и API latency middleware (`app/Http/Middleware/ApiRequestTelemetryMiddleware.php`) для structured telemetry.
- В `CatalogService` добавлены cache hit/miss и latency hooks; в `PaymentService` и `ShippingService` добавлены webhook processing/lag hooks с outcome (`processed/duplicate/rejected`) telemetry.
- Покрытие observability сервиса зафиксировано unit-тестом: `tests/Unit/ObservabilityServiceTest.php`.

- `2026-02-21`: `Phase 5` (batch 6, observability ops reporting) выполнен.
- Добавлен выделенный `observability` лог-канал (`config/logging.php`) и обновлен дефолтный telemetry channel (`config/observability.php`, `.env.example`) для изоляции operational metrics.
- Добавлена production smoke-команда `app:observability-report` с агрегированным snapshot-отчетом по `api/catalog/webhook` telemetry и поддержкой `--minutes/--json` (`app/Console/Commands/AppObservabilityReportCommand.php`).
- Добавлен feature-тест команды: `tests/Feature/ObservabilityReportCommandTest.php`.
- CI quality gate и ops scripts расширены шагом `php artisan app:observability-report --minutes=120`, документация синхронизирована (`.github/workflows/ci.yml`, `composer.json`, `README.md`).

- `2026-02-21`: `Phase 5` (batch 7, observability SLO threshold gates) выполнен.
- `app:observability-report` расширен пороговыми SLO-check опциями `--max-api-slow-rate` и `--max-webhook-lag-warn-rate` с `FAIL`-кодом при превышении бюджета (`app/Console/Commands/AppObservabilityReportCommand.php`).
- Добавлены feature-тесты threshold-сценариев и валидации опций (`tests/Feature/ObservabilityReportCommandTest.php`).
- CI и ops script обновлены до blocking observability threshold smoke-check (`.github/workflows/ci.yml`, `composer.json`), документация синхронизирована (`README.md`).

- `2026-02-21`: `Phase 5` (batch 8, observability required-samples guards) выполнен.
- В `app:observability-report` добавлены `--require-api-samples` и `--require-webhook-samples` для hard-fail при пустых окнах метрик (без silent skip) (`app/Console/Commands/AppObservabilityReportCommand.php`).
- `app:api-contract-smoke` дополнен явной записью API request telemetry в in-process HTTP checks, чтобы observability gate получал стабильные API samples в CI (`app/Console/Commands/AppApiContractSmokeCommand.php`).
- Добавлены feature-тесты required-samples сценариев (`tests/Feature/ObservabilityReportCommandTest.php`), CI/ops/doc обновлены под новые guards (`.github/workflows/ci.yml`, `composer.json`, `README.md`).
