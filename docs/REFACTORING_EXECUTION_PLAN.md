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

## Стартовый batch (следующий шаг)

1. Atomic cache version bump + tests.
2. `afterCommit` dispatch для payment/checkout side-effects.
3. Server-driven account orders filters + profile metrics endpoint.
4. Final API envelope alignment для checkout + фронтенд адаптация.
