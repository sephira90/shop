# Deep Architecture Audit & Refactoring Plan v2

> Date: 2026-03-04
> Status: Candidate backlog (not active execution authority; `Backlog F` promoted to active roadmap on 2026-03-04, `Backlog G` progressed with items `4`, `5`, `6`, `7`, `8`, and `Backlog H` progressed with items `9`, `10`, `11` on 2026-03-04)
> Source-of-truth for architecture execution: `docs/ARCHITECTURE_REFACTOR_NEXT.md`
> Previous audit: `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md`

## Scope

Полный аудит проекта shop.ru после завершения 41 wave рефакторинга и Backlog A-D.
Все пункты ниже — **новые находки**, не пересекающиеся с уже закрытыми Backlog A-E items.

## Execution Alignment

1. Этот документ не является execution source-of-truth и не конкурирует с `docs/ARCHITECTURE_REFACTOR_NEXT.md`.
2. Все пункты трактуются как candidate backlog для следующей execution program.
3. Любой пункт становится активной работой только после явного promotion в `docs/ARCHITECTURE_REFACTOR_NEXT.md`.
4. Группировка: safety first, затем domain-model expansion, затем contract discipline, затем validation, затем testing, затем frontend, затем infrastructure.

---

## Текущее состояние проекта (snapshot 2026-03-04)

Проект — production-ready e-commerce API монолит на Laravel 12 + Vue 3 (Pinia, Vue Router, Tailwind v4).

### Архитектурные достижения

- CQRS с typed DTO return boundaries
- PHPStan level 10, Psalm — 0 ошибок
- 40+ architecture guardrail-тестов
- ~116 PHPUnit тестов (~31 Feature, ~85 Unit)
- ~85 frontend Vitest specs
- Thin controllers (transport-only)
- Decomposed services (checkout, cart, webhook, admin)
- Domain layer foundation (Money VO, OrderPaymentStatusResolver, StatusTransitionSource enum)
- Status-transition domain events with afterCommit semantics
- Observability metrics, logging, notification side-effects
- Factory-based test infrastructure (14 factories, seeder coupling eliminated from feature tests)
- Docker Compose local stack + canonical ops aliases
- Operational runbook, release checklist, documentation authority guardrails

---

## Сводка по приоритетам

| Приоритет | Кол-во items | Область |
|-----------|-------------|---------|
| **P0** | 3 | Admin state-machine safety, cart validation, cart authorization |
| **P1** | 8 | Money expansion, service contracts, race conditions, domain deepening |
| **P2** | 12 | Validation, testing, frontend polish, infrastructure/caching |

---

## P0 — Safety-Critical Fixes

### 1. Admin OrderStatus transition guard

**Проблема:** `AdminOrderService` при `input->status !== null` напрямую устанавливает `OrderStatus` без проверки допустимости перехода. Admin может задать `CANCELLED -> PAID`, что нарушает state machine.

**Файл:** `app/Services/Admin/AdminOrderService.php` (строки 56-67)

**Решение:**

- Расширить `OrderStatusTransitionPolicy` методом `canTransitionDirectly(from, to): bool` с явной матрицей допустимых admin-переходов
- Добавить guard в `AdminOrderService` перед применением `status`
- Бросать `DomainException` при недопустимом переходе (маппится в 422 глобально)
- Добавить unit-тест на недопустимые admin-переходы
- Обновить `OrderStatusTransitionPolicy` до `final readonly`

---

### 2. Cart removeItem — отсутствие валидации variantId

**Проблема:** `CartController::removeItem()` принимает `variantId` из route без FormRequest и без `exists:product_variants,id` валидации. При несуществующем ID — молчаливое игнорирование.

**Файл:** `app/Http/Controllers/Api/V1/CartController.php`

**Решение:**

- Создать `RemoveCartItemRequest` с валидацией `variantId` (integer, exists:product_variants,id)
- Либо (минимально) добавить route constraint `->whereNumber('variantId')` и валидацию в сервисе
- Добавить feature-тест на удаление несуществующего варианта

---

### 3. Cart authorization/ownership

**Проблема:** `CartController` не проверяет принадлежность корзины пользователю. Guest-token/session-based подход работает, но нет явной policy.

**Решение:**

- Создать `CartPolicy` с проверкой ownership (user_id или guest_token)
- Зарегистрировать в `AppServiceProvider`
- Добавить `authorize()` вызовы в `CartController`
- Обновить `PolicyCompletenessMatrixGuardrailTest`

---

## P1 — Domain Model и Архитектурные границы

### 4. Money Value Object Expansion — Cart

**Проблема:** `CartMutationService` использует `bcmul()` для `line_total` и `$variant->price` как float. `CartResultMapper` суммирует через `$cart->items->sum('line_total')` (float).

**Файлы:**

- `app/Services/Cart/CartMutationService.php` (строка ~69-70)
- `app/Services/Cart/CartResultMapper.php` (строка ~22-23)

**Решение:**

- Заменить `bcmul` на `Money::fromDecimal($variant->price)->multiply($quantity)->toFloat()` на persistence boundary
- Использовать `Money` в `CartResultMapper` для subtotal/total вычислений
- Выровнять с паттерном из `CheckoutCartPreparer`

---

### 5. Money Value Object Expansion — DTO responses

**Проблема:** `CheckoutOrderResultDto`, `AdminOrderDetailResultDto`, `AccountOrderDetailResultDto` используют `(float) $order->subtotal/total`. `PaymentService` передаёт raw float в payment creation.

**Файлы:** множество DTO в `app/Application/*/Dto/`

**Решение:**

- Внедрить Money в DTO-маппинг для internal consistency
- Сохранить float на JSON boundary для backward-compatibility
- Передавать `Money` вместо raw float в `PaymentService`

---

### 6. Interfaces для ключевых сервисов

**Проблема:** `CheckoutService`, `CartService`, `CartMutationService` не имеют интерфейсов. Затрудняет mock-тестирование и замену реализации.

**Решение:**

- Создать interfaces в `app/Contracts/` или domain contracts:
  - `CheckoutServiceInterface`
  - `CartServiceInterface`
  - `CartMutationServiceInterface`
- Зарегистрировать в `ApplicationBindingsServiceProvider`
- Постепенно: `CatalogService`, `AdminOrderService`, `PaymentService`

---

### 7. Interfaces для remaining repositories

**Проблема:** `AdminOrderReadRepository`, `AdminProductReadRepository`, `PromotionRepository`, `CategoryRepository`, `CatalogProductReadRepository` — без контрактов.

**Решение:**

- Создать интерфейсы по паттерну `AccountOrderReadRepositoryContract`
- Зарегистрировать в провайдерах

---

### 8. ObservabilityMetricStore race condition

**Проблема:** `Cache::add` + `Cache::increment` + `Cache::put` — race при конкурентных инкрементах.

**Файл:** `app/Support/Observability/ObservabilityMetricStore.php` (строка ~317-319)

**Решение:** Использовать атомарный `Cache::increment()` с fallback или Redis INCR напрямую.

---

### 9. OrderStatusTransitionPolicy — полная state machine

**Проблема:** `OrderStatusTransitionPolicy` содержит `resolveByPaymentStatus` и `resolveByShipmentStatus`, но не имеет общего `canTransition(from, to)` с полной матрицей. Статусы `PROCESSING` и `SHIPPED` не используются в цепочке.

**Решение:**

- Добавить `canTransition(OrderStatus $from, OrderStatus $to): bool` аналогично Payment/Shipment policies
- Документировать неиспользуемые статусы (PROCESSING, SHIPPED) или удалить из enum
- Промотить до `final readonly`

---

### 10. WebhookProcessingPipeline — Throwable без логирования

**Проблема:** `WebhookProcessingPipeline` ловит `\Throwable` без логирования и без `previous`.

**Файл:** `app/Services/Webhook/WebhookProcessingPipeline.php` (строка ~117)

**Решение:** Добавить `Log::error()` с контекстом (correlation_id, event_type, receipt_id) перед возвратом `WebhookProcessingOutcome::FAILED`.

---

### 11. Exception hierarchy расширение

**Текущее:** `DomainException`, `WebhookIngressException extends DomainException`, `AuthApplicationException`.

**Решение:**

- Добавить `CartException extends DomainException`
- Добавить `CheckoutException extends DomainException`
- Добавить `OrderTransitionException extends DomainException`
- Это позволит более точный error mapping и мониторинг по типу ошибки

---

## P2 — Validation Hardening

### 12. Currency ISO 4217 validation

**Проблема:** `PlaceOrderRequest` проверяет `currency` только на `size:3`.

**Файл:** `app/Http/Requests/Checkout/PlaceOrderRequest.php` (строка ~35)

**Решение:** Добавить `Rule::in(['USD', 'EUR', 'RUB', ...])` или кастомное правило `Iso4217Currency`.

---

### 13. OrderStatusUpdateRequest — пустое тело

**Проблема:** Все поля nullable; можно отправить пустой JSON.

**Файл:** `app/Http/Requests/Admin/OrderStatusUpdateRequest.php` (строки 34-38)

**Решение:** Добавить `required_without_all` или custom rule `at_least_one_field`.

---

### 14. CatalogIndexRequest — min/max price sanity

**Файл:** `app/Http/Requests/Catalog/CatalogIndexRequest.php` (строки 30-31)

**Решение:** Добавить `gte:min_price` для `max_price`.

---

### 15. AccountOrderIndexRequest — per_page cap

**Проблема:** `per_page` до 200 — потенциальная нагрузка.

**Файл:** `app/Http/Requests/Account/AccountOrderIndexRequest.php` (строка ~30)

**Решение:** Снизить до `max:50` или добавить rate-limit по resource cost.

---

## P2 — Testing Expansion

### 16. Missing model factories

**Модели без фабрик:** `Payment`, `Shipment`, `CheckoutIdempotency`, `WebhookReceipt`, `Role`.

**Решение:** Создать фабрики для полного покрытия. `Role` через seeder-only (enum-driven).

---

### 17. Integration tests with MySQL

**Проблема:** Все тесты на SQLite. Нет гарантии совпадения поведения с MySQL 8.4 (JSON ops, locking, fulltext).

**Решение:**

- Добавить `phpunit-mysql.xml` конфигурацию
- Пометить integration-тесты группой `@group mysql`
- Запускать в CI через docker-compose

---

### 18. E2E test foundation

**Решение:**

- Добавить Playwright с 3-5 smoke сценариями: auth, catalog browse, cart -> checkout, admin CRUD
- Интегрировать в CI как optional step

---

### 19. Cleanup ExampleTest.php

**Файл:** `tests/Feature/ExampleTest.php` — шаблонный тест без ценности (Wave 23 should have removed it; if still present, delete).

---

## P2 — Frontend Polish

### 20. CartPage -> useCartActions composable

**Проблема:** `CartPage.vue` содержит 4 inline async функции (remove, increase, decrease, update).

**Файл:** `resources/js/pages/CartPage.vue` (строки 39-64)

**Решение:** Извлечь в `useCartActions(cartStore)` composable для единообразия с остальными страницами.

---

### 21. AdminDashboardPage — navItems extraction

**Решение:** Вынести статический `navItems` массив в отдельный конфигурационный файл.

---

### 22. AppShell store — добавить showSuccess

**Проблема:** `app-shell` store имеет только `showError`.

**Решение:** Добавить `showSuccess(message)` с `variant='success'` для полноты notice API.

---

## P2 — Infrastructure и Caching

### 23. Cache TTL в config

**Проблема:** `CatalogService` хардкодит TTL 5/10 минут.

**Файл:** `app/Services/Catalog/CatalogService.php` (строки 45, 66, 88)

**Решение:** Вынести в `config/catalog.php` (`cache_ttl_products`, `cache_ttl_categories`).

---

### 24. Category cache invalidation gap

**Проблема:** Изменение категорий через admin не всегда инвалидирует catalog cache (только через явный `AdminCacheService::refreshCatalogCache`).

**Решение:** Вызывать `CatalogVersionService::bump()` в admin category CRUD handlers автоматически.

---

### 25. Config externalization

Вынести в config:

- PagerDuty URL (`config/observability.php`)
- Default currency (`config/checkout.php`)
- Default per_page values (`config/pagination.php`)

---

### 26. PromotionRepository — unbounded coupons

**Проблема:** `with(['coupons' => fn...])` без limit.

**Файл:** `app/Repositories/PromotionRepository.php` (строки 23-27)

**Решение:** Добавить `->limit(100)` или пагинацию для купонов промоакции.

---

### 27. Address Value Object

**Проблема:** Адрес передаётся как массив строк в `PlaceOrderRequest` и хранится в JSON. Нет валидации структуры, нет типизации.

**Решение:**

- Создать `App\Domain\ValueObjects\Address` (final readonly)
- Использовать в `CheckoutPlaceOrderInputDto` и `CheckoutOrderWriteInputDto`
- Добавить валидацию обязательных полей (city, zip, street)

---

## Согласованный Execution Backlog

| Backlog Block | Приоритет | Items | Область | Promotion rule |
|---------------|-----------|-------|---------|----------------|
| **Backlog F** | P0 | 1, 2, 3 | Safety: admin guard, cart validation, cart authorization | First promoted block |
| **Backlog G** | P1 | 4, 5, 6, 7, 8 | Money expansion + service contracts | After Backlog F |
| **Backlog H** | P1 | 9, 10, 11 | Domain deepening: state machine, logging, exceptions | After Backlog G or parallel |
| **Backlog I** | P2 | 12, 13, 14, 15 | Validation hardening | After Backlog F |
| **Backlog J** | P2 | 16, 17, 18, 19 | Testing expansion | After Backlog I |
| **Backlog K** | P2 | 20, 21, 22 | Frontend polish | Independent |
| **Backlog L** | P2 | 23, 24, 25, 26, 27 | Infrastructure and caching | Independent |

## Recommended Promotion Order

1. Complete Backlog E items 21/22 (currently in progress)
2. Promote **Backlog F** (P0 safety)
3. Promote **Backlog G** (P1 money + contracts)
4. Promote **Backlog H** (P1 domain deepening)
5. Promote **Backlog I** (P2 validation) — can parallel with H
6. Promote **Backlog J** (P2 testing) — after I stabilizes
7. Promote **Backlog K** (P2 frontend) — independent
8. Promote **Backlog L** (P2 infra) — independent

**Суммарная оценка:** ~20-25 рабочих дней при последовательном выполнении.

---

## Acceptance Criteria (per promoted block)

- Все quality gates проходят: `composer run lint`, `composer run analyse`, `php artisan test`, `npm run lint`, `npm run lint:ox`, `npm run format:ox:check`, `npm run type-check`, `npm run test`, `npm run build`.
- Архитектурные guardrail-тесты проходят без изменений или с расширением.
- API-контракты сохранены (`/api/v1/*` backward-compatible).
- Перед началом реализации promoted block должен быть явно записан в `docs/ARCHITECTURE_REFACTOR_NEXT.md`.
- После завершения promoted block должны быть обновлены `docs/ARCHITECTURE_REFACTOR_NEXT.md` и `docs/REFACTORING_EXECUTION_PLAN.md`.
