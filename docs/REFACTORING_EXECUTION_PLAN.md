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

## Следующий batch

1. Стартовать `Phase 3`: выделить application-layer handlers для `Checkout` (command/query граница + DI wiring).
2. Подготовить extraction шаблон для `Orders`/`Promotions` на основе первого checkout handler.
