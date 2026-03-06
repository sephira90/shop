# Deep Architecture Audit & Refactoring Plan v2

> Date: 2026-03-05 (v2.3 — deep audit refresh + modular monolith migration plan + OWASP Top 10 security intake)
> Status: Candidate backlog (not active execution authority; `Backlog F` promoted to active roadmap on 2026-03-04, `Backlog G` progressed with items `4`, `5`, `6`, `7`, `8`, `Backlog G2` progressed with items `44`, `45` on 2026-03-05, and `Backlog H` progressed with items `9`, `10`, `11` on 2026-03-04)
> Source-of-truth for architecture execution: `docs/ARCHITECTURE_REFACTOR_NEXT.md`
> Previous audit: `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md`

## Scope

Полный аудит проекта shop.ru после завершения 41 wave рефакторинга и Backlog A-D.
Все пункты ниже — **новые находки**, не пересекающиеся с уже закрытыми Backlog A-E items.

Документ расширен секцией **P3 — Modular Monolith Migration** (items 28-38), секцией **Deep Audit Refresh** (items 39-76) по результатам полного code-level аудита всех слоёв (services, models, domain, handlers, controllers, requests, routes, events, jobs, listeners, policies, repositories, frontend pages/composables/stores/contracts/mappers) и секцией **OWASP Top 10 Security Intake** (items 77-83) по результатам security review auth/config/model/route boundaries.

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

## Items resolved since initial audit

Следующие items частично или полностью реализованы (подтверждено code-level review 2026-03-05):

- **Item 2** (Cart removeItem validation): `RemoveCartItemRequest` создан и подключён; route имеет `->whereNumber('variantId')`. **Закрыт.**
- **Item 3** (Cart authorization): `CartPolicy` создана и зарегистрирована в `AppServiceProvider`; `authorize()` вызовы присутствуют во всех методах `CartController`. **Частично закрыт** — ownership проверка неполная (см. item 39).
- **Item 6** (Interfaces для сервисов): `CartServiceInterface`, `CartMutationServiceInterface`, `CheckoutServiceInterface` уже созданы и зарегистрированы. **Частично закрыт** — 10+ сервисов без интерфейсов (расширено в item 53).
- **Item 7** (Interfaces для repositories): Все указанные read-repository контракты существуют и зарегистрированы. **Закрыт.**
- **Item 11** (Exception hierarchy): `CartException`, `CheckoutException`, `OrderTransitionException` созданы. **Закрыт.**

## Reality Check Addendum (2026-03-05)

This addendum reflects a direct code audit and overrides older item text where status diverges.

- **Partially closed:** item 1 (explicit direct-status path is guarded; auto-derived status path without `canTransition()` guard remains open in item 1).
- **Closed as false-positive:** item 40 (AuthUserRepository::updatePassword) because Laravel hashed cast does not rehash already hashed values.
- **Closed (verified):** item 42 (status-transition events implement `ShouldDispatchAfterCommit`; side-effect listeners dispatch jobs with `->afterCommit()`).
- **Closed (verified):** item 39 (cart mutation ownership guard now enforces authenticated owner/guest-token match before write operations).
- **Closed (verified):** item 41 (promotion/coupon counters are no longer mass assignable; counter mutation stays on explicit increment paths).
- **Closed (verified):** item 43 (first transition into `cancelled` now restores consumed inventory through a shared order-lifecycle release service used by admin and payment-webhook status sources).
- **Closed (verified):** items 44, 45 (row-level locking + `DB::transaction()` added for authenticated cart resolve and admin order status update paths).
- **Still open (business-rule decision):** item 47 (CANCELLED -> PROCESSING remains allowed by current matrix and tests).

Execution planning should prioritize remaining open safety/concurrency items and skip already closed/invalidated findings.
## Сводка по приоритетам

| Приоритет | Кол-во items | Область |
|-----------|-------------|---------|
| **P0** | 3+2+1 | Admin state-machine safety, cart ownership + inventory restore on cancel + auth token lifecycle |
| **P1** | 8+14+4 | Money expansion, service contracts, race conditions, domain deepening + race conditions, atomicity, job reliability, frontend safety, auth hardening, transport security, mass assignment |
| **P2** | 12+21+2 | Validation, testing, frontend polish, infrastructure/caching + layer hygiene, type safety, consistency, UX, code smells, data protection, security guardrails |
| **P3** | 11 | Modular monolith migration: Shared Kernel, module extraction, guardrails |
| **Closed** | 5+1 | Items 2, 7, 11 resolved; items 3→39, 6→53 refined; item 42 false positive |

---

## P0 — Safety-Critical Fixes

### 1. Admin OrderStatus transition guard — auto-derived path без guard

**Проблема:** `AdminOrderService` имеет guard `canTransition()` для **явного** указания статуса (строки 71-76), но при `input->status === null` статус заказа **авто-выводится** из изменения `paymentStatus`/`shipmentStatus` через `resolveByPaymentStatus()`/`resolveByShipmentStatus()` (строки 57-69) и применяется **без** проверки `canTransition()`. Это позволяет вывести недопустимый переход через косвенное изменение payment/shipment статуса.

**Файл:** `app/Services/Admin/AdminOrderService.php` (строки 57-76)

**Решение:**

- Добавить `canTransition()` guard для auto-derived path (после строки 69, перед записью)
- Бросать `OrderTransitionException` при недопустимом авто-переходе
- Добавить unit-тест на недопустимые auto-derived переходы

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

## Deep Audit Refresh — P0 (Critical Safety)

### 39. CartPolicy::modify() не проверяет ownership корзины

**Проблема:** `CartPolicy::modify()` (строка 22-29) разрешает **любому** аутентифицированному пользователю модифицировать **любую** корзину — нет проверки `$cart->user_id === $user->id`. Гостевая ветка тоже не сравнивает `$cart->guest_token` с предъявленным.

**Файл:** `app/Policies/CartPolicy.php`

**Решение:** Добавить явный ownership guard в cart mutation boundary: перед записью сверять owner context (`user_id` для authenticated, `guest_token` для guest) с фактически мутируемой корзиной.

> Verified 2026-03-05: `UpsertCartItemHandler`/`RemoveCartItemHandler` now pass user/guest context into `CartService`/`CartMutationService`, and mutation service rejects ownership mismatch before write with typed `CartException`.

---

### 40. AuthUserRepository::updatePassword() — избыточный Hash::make()

**Проблема:** `updatePassword()` (строка 83-88) вызывает `Hash::make($password)`, но модель `User` имеет каст `'password' => 'hashed'`. Laravel каст `'hashed'` содержит guard `Hash::isHashed()` — если значение уже хешировано, повторное хеширование **не происходит**. Таким образом, двойного хеширования нет (не баг), но вызов `Hash::make()` **избыточен** и нарушает единообразие: в `createUser()` (строка 25) пароль передаётся как plaintext и хешируется кастом.

**Файлы:** `app/Repositories/AuthUserRepository.php` (строка 83-88), `app/Models/User.php` (строка 63)

**Решение:** Убрать `Hash::make()` для единообразия с `createUser()`. Передавать plain password — каст выполнит хеширование автоматически.

> **Severity downgrade**: P0 → P2 (code smell, не security-баг).

---

### 41. Promotion usage_count / Coupon redeemed_count в $fillable — mass assignment risk

**Проблема:** `Promotion.usage_count` и `Coupon.redeemed_count` включены в `$fillable`. Реальный инкремент в `CheckoutOrderFinalizer` (строки 19-25) корректно использует атомарный `$model->increment()`. Однако наличие счётчиков в `$fillable` допускает mass assignment через `$model->update(['usage_count' => 0])` — любой admin-endpoint, принимающий эти поля, может сбросить счётчик, обходя бизнес-лимиты.

**Файлы:** `app/Models/Promotion.php` (строка 26), `app/Models/Coupon.php` (строка 20)

**Решение:** Убрать `usage_count` и `redeemed_count` из `$fillable`. Управление — только через атомарный `increment()`/`decrement()`.

> Verified 2026-03-06: `Promotion::$fillable` и `Coupon::$fillable` больше не содержат counter fields; guardrail coverage добавлена для denylist, а admin write-path regression подтверждает, что payload не может сбросить counters.

> **Severity downgrade**: P0 → P1 (mass assignment вектор, не конкурентный race в checkout flow).

---

### ~~42. Event dispatch внутри DB-транзакции~~ — FALSE POSITIVE (закрыт при верификации)

> **Верифицировано 2026-03-05:** Все domain events (`OrderPlaced`, `OrderStatusChanged`, `PaymentStatusChanged`, `ShipmentStatusChanged`) реализуют `ShouldDispatchAfterCommit`. Laravel откладывает фактическую диспетчеризацию до commit. Побочные эффекты до commit **не происходят**. Находка снята.

---

### 43. Отсутствие восстановления инвентаря при отмене заказа

**Проблема:** `AdminOrderService` при переходе заказа в `CANCELLED` (строка 78-89) не восстанавливает инвентарь, списанный `CheckoutInventoryAllocator`. Это ведёт к перманентной потере стока.

**Файл:** `app/Services/Admin/AdminOrderService.php` (строки 78-89)

**Решение:** Создать `InventoryReleaseService::release(Order $order): void`, вызывать при переходе в `CANCELLED`. Восстановить `reserved_quantity` для каждого `OrderItem`.

> Verified 2026-03-06: implemented via `app/Services/Order/OrderInventoryReleaseService.php`; the first `not-cancelled -> cancelled` transition now restores consumed `Inventory.quantity` under `lockForUpdate()` based on aggregated order-item quantities, and the shared boundary is invoked from both `AdminOrderService` and `PaymentWebhookTransitionApplier`.

---

## Deep Audit Refresh — P1 (High)

### 44. CartResolver::resolve() — race condition создания дублированных корзин

**Проблема:** Ветка для аутентифицированного пользователя (строка 21-38) не использует `lockForUpdate()` и не обёрнута в транзакцию. Два конкурентных запроса могут оба не найти корзину и создать две `ACTIVE` корзины. Гостевая ветка (строка 42-71) корректно использует `lockForUpdate()`.

**Файл:** `app/Services/Cart/CartResolver.php` (строки 21-38)

**Решение:** Обернуть authenticated ветку в `DB::transaction()` с `lockForUpdate()` по аналогии с гостевой.

> Verified 2026-03-05: implemented in `app/Services/Cart/CartResolver.php` via transaction + `lockForUpdate()` on authenticated user/cart path; missing-user branch now throws typed `CartException`.

---

### 45. AdminOrderService::updateStatus — отсутствие row-level lock

**Проблема:** Order передаётся без `lockForUpdate()`. Конкурентный webhook может обновить тот же заказ одновременно → lost update.

**Файл:** `app/Services/Admin/AdminOrderService.php` (строки 34-100)

**Решение:** Обернуть в `DB::transaction()` с `lockForUpdate()` на заказе.

> Verified 2026-03-05: implemented in `app/Services/Admin/AdminOrderService.php` by reloading order row with `lockForUpdate()` inside transaction and failing fast with typed `OrderTransitionException` when order id is stale.

---

### 46. CheckoutPlaceOrderOrchestrator — атомарность между placeOrder и initiate

**Проблема:** `placeOrder` и `paymentService->initiate()` выполняются в **отдельных** транзакциях (строки 29-46). Если `placeOrder` успешен, но `initiate` падает — заказ создан, инвентарь списан, но платёж не инициирован. Retry идемпотентно вернёт существующий order без payment.

**Файл:** `app/Services/Checkout/CheckoutPlaceOrderOrchestrator.php` (строки 29-46)

**Решение:** Обернуть весь orchestration flow в единую транзакцию. Либо сделать payment initiation retriable с compensation.

---

### 47. OrderStatusTransitionPolicy разрешает CANCELLED → PROCESSING

**Проблема:** Матрица допустимых переходов (строка 104-108) включает `OrderStatus::CANCELLED => [CANCELLED, PROCESSING, REFUNDED]`. Отменённый заказ может перейти в PROCESSING, хотя инвентарь мог быть перераспределён.

**Файл:** `app/Services/Order/OrderStatusTransitionPolicy.php` (строки 104-108)

**Решение:** Удалить `PROCESSING` из допустимых переходов `CANCELLED`. Отменённый заказ допускает только `REFUNDED`.

---

### 48. Order number collision risk

**Проблема:** `'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6))` (строка 25) — `Str::random(6)` = 36^6 ≈ 2.1B комбинаций. При высоких объёмах коллизия возможна. Нет ни unique-constraint-retry, ни sequence-based генерации.

**Файл:** `app/Services/Checkout/CheckoutOrderWriter.php` (строка 25)

**Решение:** Увеличить entropy (8+ символов) или использовать UUID/ULID. Добавить unique constraint с retry loop.

---

### 49. Jobs: отсутствуют retry/timeout/uniqueness настройки

**Проблема:** Все 5 jobs (`DispatchShipmentJob`, `SendOrderConfirmationJob`, `ProcessShippingWebhookJob`, `ProcessPaymentWebhookJob`, `SendOrderStatusChangedNotificationJob`) не имеют `$tries`, `$timeout`, `$maxExceptions`, `$backoff`. `DispatchShipmentJob` без `ShouldBeUnique` может создать дублированные отгрузки.

**Файлы:** все файлы в `app/Jobs/`

**Решение:** Для каждого job определить `$tries = 3`, `$maxExceptions = 2`, `$timeout = 30`, `$backoff = [10, 60]`. Webhook и shipment jobs — `ShouldBeUnique` с `$uniqueId`. Добавить `failed(Throwable)` для логирования permanent failures.

---

### 50. Stale variant delete без проверки ссылочной целостности

**Проблема:** `AdminProductVariantSyncService` (строки 48-54) удаляет stale-варианты без проверки наличия ссылок в `cart_items` или `order_items`. Удаление нарушает ссылочную целостность.

**Файл:** `app/Services/Admin/ProductWrites/AdminProductVariantSyncService.php` (строки 48-54)

**Решение:** Перед удалением проверять `$staleVariant->cartItems()->exists()` и `$staleVariant->orderItems()->exists()`. Бросать `DomainException` при наличии ссылок. Альтернатива — soft delete варианта.

---

### 51. OrderPlaced event передаёт Model вместо ID — inconsistency с остальными events

**Проблема:** `OrderPlaced` принимает `public readonly Order $order`, все остальные domain events (`OrderStatusChanged`, `PaymentStatusChanged`, `ShipmentStatusChanged`) передают `string $orderId`. Серилизация полной модели — риск stale data при десериализации в listener.

**Файл:** `app/Events/OrderPlaced.php` (строка 20)

**Решение:** Заменить на `string $orderId`. В listeners загружать свежую модель из БД.

---

### 52. Cart routes без throttle

**Проблема:** Группа `cart` в `routes/api.php` (строки 47-52) не имеет `throttle` middleware, в отличие от catalog (`throttle:search`), checkout (`throttle:checkout`), webhooks (`throttle:webhook`). Уязвимость для rate-abuse.

**Файл:** `routes/api.php` (строки 47-52)

**Решение:** Добавить `->middleware('throttle:60,1')` или аналогичный rate-limit.

---

### 53. Расширенный список сервисов без интерфейсов (дополнение item 6)

**Проблема:** После реализации item 6 (3 сервиса получили интерфейсы) остаётся **10 сервисов** с direct class dependency в handlers: `CatalogService`, `AdminCatalogService`, `AdminOrderService`, `AdminCategoryService`, `AdminPromotionService`, `AdminCacheService`, `PaymentService`, `CheckoutPlaceOrderOrchestrator`, `PaymentWebhookAdapter`, `ShippingWebhookAdapter`.

**Решение:** Создать интерфейсы, зарегистрировать в `ApplicationBindingsServiceProvider`. Приоритет: `PaymentService`, `CatalogService` — наиболее критичны для mock-тестирования.

---

### 54. EnsureRoleMiddleware — DB-запрос на каждый admin-запрос

**Проблема:** `$user->roles()->whereIn('name', $roles)->exists()` (строка 27) выполняет SQL-запрос при каждом запросе к admin. Роли стабильны — должны кэшироваться.

**Файл:** `app/Http/Middleware/EnsureRoleMiddleware.php` (строка 27)

**Решение:** Eager-load roles при аутентификации (Sanctum token validation) или кэшировать per-request через `$user->loadMissing('roles')`.

---

### 55. Frontend: двойной источник auth token (store vs localStorage interceptor)

**Проблема:** `api/client.ts` (строка 40) читает `localStorage.getItem('shop_api_token')` напрямую, минуя auth store. При `clearSession()` store обнуляет token, но in-flight interceptors могут прочитать stale token из localStorage.

**Файлы:** `resources/js/api/client.ts:40`, `resources/js/stores/auth.ts`

**Решение:** Interceptor должен читать token из store, не из localStorage напрямую.

---

### 56. Frontend: Register не передаёт guest_token — корзина гостя теряется

**Проблема:** Login передаёт `guest_token` (строка 69-74 в `useAuthPageViewModel.ts`), Register — нет (строка 89). `AuthRegisterPayload` не имеет поля `guest_token`. Гость добавляет товары → регистрируется → корзина теряется.

**Файлы:** `resources/js/composables/auth/useAuthPageViewModel.ts:89`, `resources/js/types/auth.ts:25-31`

**Решение:** Добавить `guest_token?: string` в `AuthRegisterPayload`. Передавать guest token при регистрации.

---

### 57. Frontend: Idempotency key генерируется при каждом клике — не стабилен

**Проблема:** `buildCheckoutIdempotencyKey()` (строки 53-62 в `validators/checkout.ts`) вызывается при каждом submit. Двойной клик → два запроса с **разными** ключами → backend создаёт два заказа.

**Файл:** `resources/js/validators/checkout.ts` (строки 53-62)

**Решение:** Генерировать ключ один раз при загрузке checkout-страницы (в `ref`), обновлять только после успешного ответа.

---

## Deep Audit Refresh — P2 (Backend)

### 58. N+1 в CartMutationService::mergeGuestCart

**Проблема:** `upsertItem()` вызывается в цикле (строки 154-159), каждый вызов — отдельная транзакция с 5+ SQL-запросами. При 10 товарах — 50+ запросов.

**Файл:** `app/Services/Cart/CartMutationService.php` (строки 154-159)

**Решение:** Batch-merge: собрать items, выполнить в одной транзакции с bulk upsert.

---

### 59. Float-precision loss при создании Money из Eloquent attributes

**Проблема:** `(float) $item->line_total` и `(float) $item->unit_price` (строки 49, 57 в `CheckoutCartPreparer`) теряют точность при касте string→float перед передачей в `Money::fromDecimal()`. Аналогично в `CartResultMapper` и `CartMutationService`.

**Файлы:** `app/Services/Checkout/CheckoutCartPreparer.php:49,57`, `app/Services/Cart/CartResultMapper.php:44-45`

**Решение:** Передавать string напрямую в Money (если `fromDecimal` поддерживает `string|float`), или использовать `Money::fromString()`.

---

### 60. Handlers с прямым доступом к Eloquent, минуя repository

**Проблема:** `GetAdminOrderDetailHandler` и `GetAdminCategoryDetailHandler` вызывают `$query->order->load(...)` напрямую вместо использования repository contracts, которые существуют и используются в list-queries.

**Файлы:** `app/Application/Admin/Orders/Queries/GetAdminOrderDetailHandler.php:14-19`, `app/Application/Admin/Categories/Queries/GetAdminCategoryDetailHandler.php:9-22`

**Решение:** Добавить `loadForAdmin(Order $order)` в `AdminOrderReadRepository` (по аналогии с `AdminProductReadRepository::loadForAdmin()`). Аналогично для категорий.

---

### 61. Auth handlers возвращают raw string вместо DTO

**Проблема:** `ForgotAuthPasswordHandler`, `ResetAuthPasswordHandler`, `VerifyAuthEmailHandler`, `ResendAuthVerificationHandler` возвращают `string`, контроллер строит `['message' => $message]` вручную — DTO-маппинг, просочившийся в транспорт.

**Файлы:** 4 handler-а в `app/Application/Auth/Commands/`, соответствующие контроллеры

**Решение:** Создать `AuthMessageResultDto` с методом `toArray()`.

---

### 62. Двойная авторизация (FormRequest + Controller) в 10 точках

**Проблема:** FormRequest проверяет `authorize()` с `$this->user()->can(...)`, затем контроллер повторно вызывает `$this->authorize(...)`. Нарушает SRP — авторизация в двух местах.

**Файлы:** 10 пар FormRequest + Controller (ProductStore/Update, CategoryStore/Update, OrderStatusUpdate, PromotionStore/Update, CouponStore/Update)

**Решение:** Убрать `authorize()` из FormRequest (вернуть `true`), оставить только в контроллере. Или наоборот — унифицировать.

---

### 63. Webhook controllers — дублированный signature-check код

**Проблема:** `PaymentWebhookController` и `ShippingWebhookController` (строки 30-33) содержат идентичный блок проверки `X-Signature` header.

**Файлы:** оба webhook контроллера

**Решение:** Извлечь в middleware `EnsureWebhookSignatureMiddleware` или trait.

---

### 64. ShippingWebhookController возвращает HTTP 200 вместо 202

**Проблема:** Shipping webhook ставит задачу в очередь, но возвращает `200` с `'processed' => true`. Payment webhook корректно возвращает `202` с `'queued' => true`.

**Файлы:** `app/Http/Controllers/Api/V1/Webhook/ShippingWebhookController.php:48`

**Решение:** Унифицировать: оба webhook — HTTP 202 с `['queued' => true]`.

---

### 65. LIKE-поиск без экранирования спецсимволов

**Проблема:** Все репозитории конкатенируют пользовательский ввод в LIKE-паттерн (`'%'.$search.'%'`) без экранирования `%` и `_`. Ввод `%admin%` вернёт все записи.

**Файлы:** `app/Repositories/Concerns/AppliesOrderSearch.php:21`, `CategoryRepository.php:29`, `AdminProductReadRepository.php:31`, `CatalogProductReadRepository.php:94-98`

**Решение:** Создать `SearchQuery::escapeLike(string $term): string` в `app/Support/`, использовать повсюду.

---

### 66. Все модели не final

**Проблема:** Все 17 моделей объявлены без `final`, хотя listeners, policies и providers уже используют `final class` / `final readonly class`. Допускает неконтролируемое наследование.

**Файлы:** все файлы в `app/Models/`

**Решение:** Пометить все модели `final class`. Исключение: `User extends Authenticatable` (если нужен Mockery mock).

---

### 67. Inconsistent `final` и `readonly` на контроллерах и handlers

**Проблема:** Только 1 из 14 контроллеров (`AccountOrdersController`) объявлен `final`. Только 2 из 44 handlers объявлены `final readonly class`, остальные — `final class` (хотя все stateless).

**Файлы:** все контроллеры и handlers

**Решение:** Все контроллеры → `final class`. Все stateless handlers → `final readonly class`.

---

### 68. SendOrderStatusChangedNotificationJob — деградация типов (enum → string)

**Проблема:** Job принимает `previousStatus`, `currentStatus`, `source` как `string` (строки 22-25), хотя dispatch передаёт `->value` enum-ов. Теряет type safety.

**Файл:** `app/Jobs/SendOrderStatusChangedNotificationJob.php` (строки 22-25)

**Решение:** Заменить на typed параметры: `OrderStatus`, `StatusTransitionSource`. PHP backed enums корректно сериализуются в Laravel queue.

---

### 69. CatalogProductReadRepository::withQueryString() — view concern в repository

**Проблема:** `->withQueryString()` (строка 67) — preservation query-параметров в pagination links — является presentation/transport concern.

**Файл:** `app/Repositories/CatalogProductReadRepository.php` (строка 67)

**Решение:** Убрать из repository. Применять в controller/query handler.

---

### 70. Enums без доменных предикатов (isFinal, isTerminal)

**Проблема:** Ни один status-enum не содержит `isFinal(): bool` для определения терминальных состояний. Проверки дублируются в сервисах.

**Файлы:** `app/Enums/OrderStatus.php`, `PaymentStatus.php`, `ShipmentStatus.php`

**Решение:** Добавить `isFinal(): bool` (COMPLETED/CANCELLED/REFUNDED → true) в каждый status-enum.

---

### 71. Money VO — отсутствуют comparison и predicate методы

**Проблема:** `Money` содержит арифметику, но нет `equals()`, `isZero()`, `isPositive()`, `greaterThan()`. Потребители сравнивают через `$money->amountCents() === 0` — утечка internal representation.

**Файл:** `app/Domain/ValueObjects/Money.php`

**Решение:** Добавить comparison/predicate методы. `equals()` должен проверять и `amountCents`, и `currency`.

---

### 72. AuthApplicationException не принимает $previous

**Проблема:** Конструктор (строки 14-17) не принимает `$previous` exception. По правилам проекта (AGENTS.md) исключения должны пробрасывать original stack trace.

**Файл:** `app/Application/Auth/AuthApplicationException.php` (строки 14-17)

**Решение:** Добавить `?\Throwable $previous = null` в конструктор, пробросить в `parent::__construct()`.

---

### 73. ApiResponse::error() — global request() helper

**Проблема:** Статический метод (строка 66-67) вызывает `request()` helper — скрытая зависимость от глобального состояния. Не работает в console/queue контексте.

**Файл:** `app/Support/Api/ApiResponse.php` (строки 66-67)

**Решение:** Принимать `?string $correlationId = null` как параметр. Вызывающий код передаёт из request.

---

## Deep Audit Refresh — P2 (Frontend)

### 74. Дублирование ensureUserLoaded — race condition при старте

**Проблема:** И `App.vue` (строки 28-37), и `router/index.ts` (строки 79-84) вызывают `authStore.ensureUserLoaded()` при первой загрузке. Оба триггера инициируют HTTP-запрос `GET /auth/me` — двойной сетевой запрос + race на присваивание `this.user`.

**Файлы:** `resources/js/App.vue:28-37`, `resources/js/router/index.ts:79-84`

**Решение:** Оставить загрузку только в router guard (блокирует навигацию до resolve), удалить из `App.vue`. Или сделать `ensureUserLoaded` Promise-deduplication.

---

### 75. Нет 404 / catch-all route и нет chunk error handling

**Проблема:** Нет `{ path: '/:pathMatch(.*)*', component: NotFoundPage }`. Все страницы lazy-loaded — при chunk load failure (deploy, сеть) — белый экран без обратной связи. Нет `router.onError()`.

**Файл:** `resources/js/router/index.ts`

**Решение:** Добавить catch-all route с `NotFoundPage.vue`. Добавить `router.onError()` для chunk failures с retry/fallback UI.

---

### 76. Storage key дублирование без общих констант

**Проблема:** `shop_guest_token` дублирован в 3 файлах (`stores/cart.ts`, `checkoutPageEffects.ts`, `authPageEffects.ts`), `shop_api_token` — в 2 (`stores/auth.ts`, `api/client.ts`). Checkout/auth effects обращаются к `localStorage` напрямую, минуя `StorageAdapter`.

**Файлы:** `resources/js/stores/cart.ts:9`, `resources/js/stores/auth.ts:14`, `resources/js/api/client.ts:40`, `resources/js/composables/checkout/checkoutPageEffects.ts:12`, `resources/js/composables/auth/authPageEffects.ts:10`

**Решение:** Вынести ключи в `utils/storage-keys.ts`. Checkout/auth effects должны использовать `StorageAdapter`.

---

## OWASP Top 10 Security Intake

Ниже заведены только **новые** security-items, которых ещё нет в candidate backlog. Уже открытые items `41`, `52` и `65` входят в ту же security program и должны продвигаться вместе с соответствующими security blocks ниже, а не рассматриваться как отдельная несвязанная работа.

## Security Intake — P0

### 77. Sanctum access-token lifecycle и revalidation активного пользователя

**Проблема:** `config/sanctum.php` оставляет `'expiration' => null`, поэтому bearer tokens живут бессрочно. Route groups с `auth:sanctum` в `routes/api.php` не выполняют отдельную revalidation `is_active`, а `AuthUserRepository::revokeCurrentAccessToken()` удаляет только текущий token. В результате украденный или stale token может пережить logout, password reset и деактивацию пользователя дольше допустимого окна риска.

**Файлы:** `config/sanctum.php:50`, `routes/api.php:31-67`, `app/Repositories/AuthUserRepository.php:49-52`

**Решение:**

- Ввести finite TTL для Sanctum tokens через env/config вместо `null`
- Добавить middleware `EnsureActiveApiUser` во все `auth:sanctum` route groups
- Разделить semantics current-session logout и global token revoke; full revoke обязателен при deactivation/password reset
- Добавить feature-тесты на expired token, disabled user c ранее выданным token и global revoke path

---

## Security Intake — P1

### 78. Auth credential hardening: password policy, identity lockout, anti-enumeration

**Проблема:** `RegisterRequest` и `ResetPasswordRequest` ограничивают пароль только `min:8`; login route использует generic `throttle:6,1` без identity-aware lockout; `LoginAuthUserHandler` завершает unknown-email path до hash workload, оставляя timing gap между существующим и несуществующим email.

**Файлы:** `app/Http/Requests/Auth/RegisterRequest.php:27-33`, `app/Http/Requests/Auth/ResetPasswordRequest.php:27-31`, `routes/api.php:22-38`, `app/Application/Auth/Commands/LoginAuthUserHandler.php:28-45`

**Решение:**

- Перевести парольные правила на `Illuminate\Validation\Rules\Password` с явной policy complexity
- Ввести отдельный `RateLimiter::for('auth.login')`, ключуемый по normalized email + IP, с временным lockout
- Нормализовать failure path через dummy hash check при unknown email
- Добавить feature/unit coverage на weak password rejection, repeated failed login lockout и unknown-email parity

---

### 79. Отсутствует auth security audit trail

**Проблема:** Успешные и неуспешные login attempts, token issuance/revocation, logout и password reset completion не эмитят явные security logs/events. Инцидентный разбор brute-force, stolen-token reuse и suspicious auth activity сейчас опирается только на косвенные rate-limit симптомы.

**Файлы:** `app/Application/Auth/Commands/*`, `app/Repositories/AuthUserRepository.php`

**Решение:**

- Добавить структурированные security events/log records: `auth.login.success`, `auth.login.failed`, `auth.logout`, `auth.password.reset`
- Логировать correlation id, user id или email hash, IP и user-agent fingerprint без password/token leakage
- Подключить эти события к текущему observability pipeline/alerts
- Добавить feature или smoke assertions на наличие audit trail для критичных auth flows

---

### 80. Sensitive privilege/state fields остаются mass assignable

**Проблема:** `User::$fillable` содержит `is_active`, а `Order::$fillable` — `status`, `payment_status`, `shipment_status`. Даже если сегодня эти поля не приходят напрямую из public transport boundary, они расширяют attack surface и превращают будущую ошибку mapping/controller/service в privilege/state escalation. Соседний security item того же класса — item `41` (`usage_count` / `redeemed_count`).

**Файлы:** `app/Models/User.php:33-40`, `app/Models/Order.php:22-39`

**Решение:**

- Убрать privilege/state fields из `$fillable`
- Оставить только явные service/repository mutation paths для activation и status transitions
- Добавить guardrail-тест на sensitive attributes в `$fillable`
- Закрывать вместе с item `41` как единый mass-assignment hardening slice

---

### 81. Transport security baseline зафиксирован только конвенцией

**Проблема:** В репозитории отсутствует явный `config/cors.php`; `session.secure` зависит от `SESSION_SECURE_COOKIE`, которого нет в `.env.example`; proxy-aware HTTPS enforcement не закодирован в middleware/bootstrap policy. Без этих явных конфигураций безопасность cookies/CORS зависит от deployment discipline, а не от versioned source-of-truth.

**Файлы:** `config/session.php:172`, `.env.example:32-37`, `config/cors.php` (отсутствует), `routes/api.php`

**Решение:**

- Добавить явный `config/cors.php` с env-driven allowlist
- Ввести proxy-aware HTTPS enforcement для non-local environments
- Добавить `SESSION_SECURE_COOKIE` в `.env.example` и зафиксировать secure-cookie policy
- Добавить bootstrap/feature coverage для CORS и secure transport invariants

---

## Security Intake — P2

### 82. Нет data-classification / encryption plan для PII и payment payload

**Проблема:** `Order` хранит billing/shipping address как plaintext JSON, а `Payment` хранит provider payload как plaintext JSON. Для текущего runtime это функционально удобно, но без явной data-classification/minimization стратегии увеличивает blast radius при утечке и усложняет дальнейшее compliance hardening.

**Файлы:** `app/Models/Order.php:22-69`, `app/Models/Payment.php:16-37`

**Решение:**

- Зафиксировать inventory действительно нужных полей для account/support/webhook flows
- Минимизировать payload до business-required subset перед сохранением
- Ввести field-level encryption или encrypted casts для чувствительных address/payment subfields
- Подготовить retention/migration plan для уже сохранённых записей

---

### 83. Security invariants не защищены автоматическими guardrails

**Проблема:** Даже после реализации items `77-82` security-инварианты останутся mostly convention-based. Сейчас нет тестов, которые бы жёстко держали non-null token expiration, active-user middleware на auth routes, sensitive `$fillable` denylist и security-aligned config presence.

**Файлы:** `config/sanctum.php`, `routes/api.php`, `app/Models/User.php`, `app/Models/Order.php`, `tests/Unit/Architecture/*`

**Решение:**

- Добавить `tests/Unit/Architecture/SecurityConfigGuardrailTest.php`
- Добавить `tests/Unit/Architecture/SensitiveFillableGuardrailTest.php`
- Добавить security smoke coverage для expired token / disabled user / auth lockout
- Явно включить в ту же guardrail program existing items `52` (cart throttle) и `65` (LIKE escaping)

---

## Согласованный Execution Backlog

### Стратегия миграции

Переход к физической модульной структуре `app/Domains/*` выполняется **инкрементально** (один модуль за раз) при соблюдении инвариантов:

1. **Zero downtime**: `/api/v1/*` envelope не ломается ни на одном шаге.
2. **Backward compatibility**: PSR-4 namespace aliasing через `class_alias` или Laravel service provider rebinding на переходный период, если потребители используют старый FQCN.
3. **Green quality gate**: каждый перенесённый модуль проходит полный CI до мержа.
4. **One module per block**: не переносить два домена одновременно, чтобы изолировать регрессии.

### Предпосылки (gate-условия старта P3)

Миграция P3 начинается **только после** завершения:

- **Backlog G** (items 6, 7) — интерфейсы для сервисов и репозиториев обязательны, иначе перенос создаст жёсткую связанность через конкретные классы.
- **Backlog H** (item 11) — доменная exception hierarchy должна быть финализирована до переноса exception-файлов в модули.
- **Backlog L** (item 25) — config externalization завершена, модули не должны хардкодить значения.

### Shared Kernel — `app/Shared/`

Код, используемый **≥3 доменами**, выносится в Shared Kernel вместо дублирования по модулям.

```text
app/Shared/
  Enums/
    OrderStatus.php          ← используется Orders, Payments, Checkout, Admin, Webhooks
    PaymentStatus.php        ← используется Payments, Orders, Checkout, Webhooks
    ShipmentStatus.php       ← используется Orders, Payments, Webhooks
    CartStatus.php           ← используется Cart, Checkout
    ProductStatus.php        ← используется Catalog, Cart, Admin
    PromotionType.php        ← используется Checkout, Admin
    RoleName.php             ← используется Auth, Admin
  ValueObjects/
    Money.php                ← используется Cart, Checkout, Payments
  Exceptions/
    DomainException.php      ← базовый, все домены
  Events/
    OrderStatusChanged.php   ← Orders (publish), Payments/Shipping/Admin (subscribe)
    PaymentStatusChanged.php ← Payments (publish), Orders/Admin (subscribe)
    ShipmentStatusChanged.php ← Shipping (publish), Orders/Admin (subscribe)
    OrderPlaced.php          ← Checkout (publish), Orders/Payments (subscribe)
  Contracts/
    PaymentGatewayInterface.php
    ShippingGatewayInterface.php
  Support/                   ← rename текущего app/Support (observability, maintenance, smoke, data)
```

**Правило**: Shared Kernel — read-only для модулей. Изменения в Shared Kernel требуют review от владельцев всех зависимых модулей.

### Target Module Internal Layout

Каждый модуль `app/Domains/{Module}/` конвергирует к единой физической структуре:

```text
app/Domains/{Module}/
  Controllers/           ← transport (из app/Http/Controllers/Api/V1/{context})
  Requests/              ← validation boundary (из app/Http/Requests/{context})
  Commands/              ← write handlers (из app/Application/{context}/Commands)
  Queries/               ← read handlers (из app/Application/{context}/Queries)
  Contracts/             ← module-owned interfaces (из app/Application/{context}/Contracts)
  Dto/                   ← handler I/O boundaries (из app/Application/{context}/Dto)
  Services/              ← business orchestration (из app/Services/{context})
  Repositories/          ← persistence (из app/Repositories/{context}*)
  Models/                ← Eloquent entities owned by this module
  Domain/                ← module-specific value objects, exceptions, policies
  Jobs/                  ← async work owned by this module
  Listeners/             ← event subscribers owned by this module
  Policies/              ← authorization policies for module models
  Providers/             ← DomainServiceProvider (auto-discovered)
  Tests/                 ← co-located module tests (optional, phased)
```

Каждый модуль регистрирует свой `{Module}ServiceProvider` с:

- bindings (contract → implementation);
- event subscribers;
- route registration (или делегация в `routes/api.php` с group prefix).

### Module Ownership Map

Текущее расположение → целевой модуль.

#### 28. Catalog Module

| Текущий путь | Целевой путь |
|-------------|-------------|
| `Http/Controllers/Api/V1/CatalogController` | `Domains/Catalog/Controllers/CatalogController` |
| `Http/Requests/Catalog/*` | `Domains/Catalog/Requests/*` |
| `Application/Catalog/Queries/*` | `Domains/Catalog/Queries/*` |
| `Application/Catalog/Dto/*` | `Domains/Catalog/Dto/*` |
| `Application/Catalog/Contracts/*` | `Domains/Catalog/Contracts/*` |
| `Services/Catalog/CatalogService` | `Domains/Catalog/Services/CatalogService` |
| `Services/Catalog/CatalogVersionService` | `Domains/Catalog/Services/CatalogVersionService` |
| `Repositories/CatalogProductReadRepository` | `Domains/Catalog/Repositories/CatalogProductReadRepository` |
| `Models/Product`, `ProductVariant`, `Category`, `Price`, `Inventory` | `Domains/Catalog/Models/*` |

**Внешние зависимости модуля:** `Shared/Enums/ProductStatus`, `Shared/Support/ObservabilityService`.
**Публикуемые контракты:** `CatalogProductReadRepository` (read-only, потребитель: Cart, Checkout).

**Сложность:** Низкая — наиболее изолированный read-heavy модуль, минимальные внешние зависимости.

---

#### 29. Cart Module

| Текущий путь | Целевой путь |
|-------------|-------------|
| `Http/Controllers/Api/V1/CartController` | `Domains/Cart/Controllers/CartController` |
| `Http/Requests/Cart/*` | `Domains/Cart/Requests/*` |
| `Application/Cart/Commands/*` | `Domains/Cart/Commands/*` |
| `Application/Cart/Queries/*` | `Domains/Cart/Queries/*` |
| `Application/Cart/Dto/*` | `Domains/Cart/Dto/*` |
| `Services/Cart/*` | `Domains/Cart/Services/*` |
| `Models/Cart`, `CartItem` | `Domains/Cart/Models/*` |
| `Policies/CartPolicy` | `Domains/Cart/Policies/CartPolicy` |
| `Contracts/CartServiceInterface`, `CartMutationServiceInterface` | `Domains/Cart/Contracts/*` |
| `Domain/Exceptions/CartException` | `Domains/Cart/Domain/CartException` |

**Внешние зависимости:** `Shared/Enums/CartStatus`, `Shared/Enums/ProductStatus`, `Shared/ValueObjects/Money`, `Catalog/Models/ProductVariant` (read-only для price/stock check).
**Публикуемые контракты:** `CartServiceInterface`, `CartMutationServiceInterface` (потребитель: Checkout).

**Сложность:** Низкая-средняя — зависимость от Catalog ограничена read-only доступом к `ProductVariant`.

---

#### 30. Users & Auth Module

| Текущий путь | Целевой путь |
|-------------|-------------|
| `Http/Controllers/Api/V1/Auth/*` | `Domains/Users/Controllers/*` |
| `Http/Controllers/Api/V1/Account/*` | `Domains/Users/Controllers/*` |
| `Http/Requests/Auth/*`, `Account/*` | `Domains/Users/Requests/*` |
| `Application/Auth/*` | `Domains/Users/Auth/*` |
| `Application/Account/Orders/*` | `Domains/Users/AccountOrders/*` |
| `Repositories/AuthUserRepository`, `AuthPasswordBrokerRepository` | `Domains/Users/Repositories/*` |
| `Repositories/AccountOrderReadRepository` | `Domains/Users/Repositories/AccountOrderReadRepository` |
| `Models/User`, `Role` | `Domains/Users/Models/*` |

**Внешние зависимости:** `Shared/Enums/RoleName`, `Orders/Models/Order` (read-only для account order listing).
**Публикуемые контракты:** `AuthUserRepository` (потребитель: Checkout для identity resolution).

**Сложность:** Средняя — account order reads зависят от `Order` модели, но это read-only cross-context query через контракт.

---

#### 31. Orders Module

| Текущий путь | Целевой путь |
|-------------|-------------|
| `Http/Controllers/Api/V1/Admin/OrderController` | `Domains/Orders/Controllers/AdminOrderController` |
| `Http/Requests/Admin/Order*` | `Domains/Orders/Requests/*` |
| `Application/Admin/Orders/*` | `Domains/Orders/Admin/*` |
| `Services/Admin/AdminOrderService` | `Domains/Orders/Services/AdminOrderService` |
| `Services/Order/OrderStatusTransitionPolicy` | `Domains/Orders/Domain/OrderStatusTransitionPolicy` |
| `Repositories/AdminOrderReadRepository` | `Domains/Orders/Repositories/AdminOrderReadRepository` |
| `Models/Order`, `OrderItem` | `Domains/Orders/Models/*` |
| `Domain/Order/OrderPaymentStatusResolver` | `Domains/Orders/Domain/OrderPaymentStatusResolver` |
| `Domain/Order/StatusTransitionSource` | `Shared/Enums/StatusTransitionSource` (или `Domains/Orders/Domain/`) |
| `Domain/Exceptions/OrderTransitionException` | `Domains/Orders/Domain/OrderTransitionException` |
| `Policies/OrderPolicy` | `Domains/Orders/Policies/OrderPolicy` |
| `Jobs/SendOrderConfirmationJob` | `Domains/Orders/Jobs/SendOrderConfirmationJob` |
| `Jobs/SendOrderStatusChangedNotificationJob` | `Domains/Orders/Jobs/SendOrderStatusChangedNotificationJob` |

**Внешние зависимости:** `Shared/Enums/{OrderStatus,PaymentStatus,ShipmentStatus}`, `Shared/Events/*`, `Payments/Services/PaymentStatusTransitionPolicy` (read), `Shipping/ShipmentStatusTransitionPolicy` (read).
**Публикуемые контракты:** `OrderStatusTransitionPolicy`, `AdminOrderReadRepository`, `OrderPaymentStatusResolver` (потребители: Checkout, Payments, Webhooks, Admin facade).

**Сложность:** Высокая — центральный агрегат с зависимостями от Payment/Shipment transition policies. `AdminOrderService` оркестрирует три transition policy из трёх доменов.

---

#### 32. Payments Module

| Текущий путь | Целевой путь |
|-------------|-------------|
| `Services/Payment/PaymentService` | `Domains/Payments/Services/PaymentService` |
| `Services/Payment/PaymentWebhookAdapter` | `Domains/Payments/Services/PaymentWebhookAdapter` |
| `Services/Payment/PaymentWebhookIngressResolver` | `Domains/Payments/Services/PaymentWebhookIngressResolver` |
| `Services/Payment/PaymentWebhookTransitionApplier` | `Domains/Payments/Services/PaymentWebhookTransitionApplier` |
| `Services/Payment/PaymentStatusTransitionPolicy` | `Domains/Payments/Domain/PaymentStatusTransitionPolicy` |
| `Models/Payment` | `Domains/Payments/Models/Payment` |
| `Contracts/PaymentGatewayInterface` | `Shared/Contracts/PaymentGatewayInterface` |
| `Infrastructure/FakePaymentGateway` | `Domains/Payments/Infrastructure/FakePaymentGateway` |
| `Jobs/ProcessPaymentWebhookJob` | `Domains/Payments/Jobs/ProcessPaymentWebhookJob` |

**Внешние зависимости:** `Shared/Contracts/PaymentGatewayInterface`, `Shared/ValueObjects/Money`, `Shared/Events/PaymentStatusChanged`, `Webhooks/WebhookProcessingPipeline`, `Orders/Models/Order` (read + write payment).
**Публикуемые контракты:** `PaymentStatusTransitionPolicy`, `PaymentService` interface (потребитель: Checkout, Webhooks).

**Сложность:** Высокая — тесная связь с Webhooks pipeline и Order aggregate.

---

#### 33. Checkout Module

| Текущий путь | Целевой путь |
|-------------|-------------|
| `Http/Controllers/Api/V1/CheckoutController` | `Domains/Checkout/Controllers/CheckoutController` |
| `Http/Requests/Checkout/*` | `Domains/Checkout/Requests/*` |
| `Application/Checkout/Commands/*` | `Domains/Checkout/Commands/*` |
| `Application/Checkout/Dto/*` | `Domains/Checkout/Dto/*` |
| `Services/Checkout/*` (все 8 сервисов) | `Domains/Checkout/Services/*` |
| `Models/CheckoutIdempotency` | `Domains/Checkout/Models/CheckoutIdempotency` |
| `Contracts/CheckoutServiceInterface` | `Domains/Checkout/Contracts/CheckoutServiceInterface` |
| `Domain/Exceptions/CheckoutException` | `Domains/Checkout/Domain/CheckoutException` |

**Внешние зависимости:** `Cart/Contracts/CartServiceInterface`, `Orders/Models/Order` (write), `Payments/Services/PaymentService`, `Shared/ValueObjects/Money`, `Shared/Events/OrderPlaced`, `Catalog/Models/Inventory` (allocation).
**Публикуемые контракты:** `CheckoutServiceInterface`.

**Сложность:** Высокая — orchestrator-модуль, зависит от Cart, Orders, Payments, Catalog (inventory).

---

#### 34. Webhooks Module

| Текущий путь | Целевой путь |
|-------------|-------------|
| `Http/Controllers/Api/V1/Webhook/*` | `Domains/Webhooks/Controllers/*` |
| `Application/Webhook/Commands/*` | `Domains/Webhooks/Commands/*` |
| `Services/Webhook/WebhookProcessingPipeline` | `Domains/Webhooks/Services/WebhookProcessingPipeline` |
| `Services/Webhook/Webhook*` (interfaces, enums, exceptions) | `Domains/Webhooks/Domain/*` |
| `Services/Shipping/Shipping*Webhook*` | `Domains/Webhooks/Adapters/ShippingWebhookAdapter` (или `Domains/Shipping/`) |
| `Models/WebhookReceipt` | `Domains/Webhooks/Models/WebhookReceipt` |
| `Models/Shipment` | `Domains/Webhooks/Models/Shipment` (или `Domains/Orders/Models/`) |
| `Jobs/Process*WebhookJob` | `Domains/Webhooks/Jobs/*` |

**Внешние зависимости:** `Payments/Services/PaymentWebhookAdapter`, `Orders/Models/Order`, `Shared/Support/ObservabilityService`.
**Публикуемые контракты:** `WebhookProcessingPipeline`, `WebhookProcessorAdapterInterface`.

**Сложность:** Средняя — unified pipeline, адаптеры для Payment/Shipping.

**Решение по Shipping:** Shipping не выделяется в отдельный модуль (недостаточный объём кода). Shipping-сервисы и `ShipmentStatusTransitionPolicy` размещаются в `Domains/Orders/Services/Shipping/`, а webhook-адаптеры — в `Domains/Webhooks/Adapters/`.

---

#### 35. Admin Module (фасад)

Admin не выделяется в отдельный модуль `Domains/Admin/`. Вместо этого:

- Admin CRUD для products/categories → `Domains/Catalog/Admin/*`
- Admin CRUD для orders → `Domains/Orders/Admin/*`
- Admin CRUD для promotions → `Domains/Checkout/Admin/*` (промоакции привязаны к checkout discount flow)
- Admin cache refresh → `Domains/Catalog/Admin/Cache/`

**Обоснование:** Admin — это не bounded context, а набор use-case-ов над существующими доменами. Выделение Admin в отдельный модуль создаёт фиктивный домен без собственных бизнес-правил и порождает circular зависимости.

---

### Cross-Module Communication Rules

1. **Синхронная зависимость** — только через контракт (interface) из `{Module}/Contracts/` или `Shared/Contracts/`. Прямые `use` на конкретные классы чужого модуля запрещены (кроме read-only DTO/Value Object).
2. **Асинхронная зависимость** — через domain events из `Shared/Events/`. Публикует event модуль-владелец; подписываются модули-потребители через собственные Listeners.
3. **Read-only cross-context queries** — допускаются через явный read-repository контракт (пример: `AccountOrderReadRepository` в Users читает Orders). Запись в чужой агрегат запрещена.
4. **Model sharing** — модели, принадлежащие модулю, могут читаться другими через Eloquent relations, но write-операции разрешены только модулю-владельцу. Ownership фиксируется в module README.

### Guardrail Extensions для P3

#### 36. Module Boundary Guardrail Test

Архитектурный тест, проверяющий:

- `Domains/{Module}/Services/*` не импортирует `Domains/{OtherModule}/Services/*` напрямую (только через Contracts);
- `Domains/{Module}/Repositories/*` не импортирует другие модули;
- `Domains/{Module}/Controllers/*` не импортирует `Domains/{OtherModule}/Controllers/*`;
- `Domains/{Module}/Models/*` для write-операций используется только внутри своего модуля.

**Файл:** `tests/Unit/Architecture/ModuleBoundaryGuardrailTest.php`

---

#### 37. Module Provider Registration Guardrail

Архитектурный тест, проверяющий:

- Каждый `app/Domains/*/Providers/*ServiceProvider.php` зарегистрирован в `bootstrap/providers.php` или `config/app.php`;
- Каждый модуль имеет ровно один ServiceProvider;
- ServiceProvider не регистрирует bindings для чужих модулей.

**Файл:** `tests/Unit/Architecture/ModuleProviderGuardrailTest.php`

---

#### 38. Shared Kernel Stability Guardrail

Архитектурный тест, проверяющий:

- `app/Shared/*` не импортирует ничего из `app/Domains/*` (dependency inversion: modules depend on Shared, not vice versa);
- Shared Kernel содержит только enums, value objects, exceptions, events и infrastructure contracts.

**Файл:** `tests/Unit/Architecture/SharedKernelStabilityGuardrailTest.php`

---

### Migration Phases (порядок переноса)

Модули переносятся в порядке возрастания связанности:

| Фаза | Модуль | Зависит от | Сложность | Оценка |
|------|--------|------------|-----------|--------|
| **M1** | Shared Kernel extraction | — | Низкая | 2 дня |
| **M2** | Catalog | Shared | Низкая | 3 дня |
| **M3** | Cart | Shared, Catalog (read) | Низкая-средняя | 2 дня |
| **M4** | Users & Auth | Shared, Orders (read) | Средняя | 3 дня |
| **M5** | Orders | Shared, Payments (policy read) | Высокая | 4 дня |
| **M6** | Payments | Shared, Orders, Webhooks | Высокая | 3 дня |
| **M7** | Webhooks | Shared, Payments, Orders | Средняя | 2 дня |
| **M8** | Checkout | Shared, Cart, Orders, Payments, Catalog | Высокая | 4 дня |

**Суммарная оценка M1–M8:** ~23 рабочих дней.

### Порядок действий при переносе одного модуля

1. Создать `app/Domains/{Module}/Providers/{Module}ServiceProvider.php` с bindings.
2. Перенести файлы с обновлением namespace (PSR-4).
3. Обновить `composer.json` autoload для `App\\Domains\\{Module}\\`.
4. Зарегистрировать `{Module}ServiceProvider` в `bootstrap/providers.php`.
5. Удалить старые bindings из `ApplicationBindingsServiceProvider` (или `AppServiceProvider`).
6. Обновить `routes/api.php` — route group использует новый controller FQCN.
7. Добавить `class_alias` в `{Module}ServiceProvider` для backward compatibility (если есть внешние потребители старого FQCN); удалить alias через 1 sprint.
8. Обновить architecture guardrail-тесты.
9. Запустить полный quality gate.
10. Обновить module README с ownership map и published contracts.

### Risks и Mitigation

| Риск | Митигация |
|------|-----------|
| Массовый namespace rename ломает IDE/autocompletion | `composer dump-autoload` + один модуль за раз |
| Circular dependency между Orders и Payments | Transition policies → Shared Kernel или domain events |
| `Shipment` model ownership спорна (Orders vs Webhooks) | Shipment → `Domains/Orders/Models/`, webhook adapters → `Domains/Webhooks/` |
| Admin routes разбросаны по модулям | Единый `routes/admin.php` с prefix `api/v1/admin`, модули регистрируют свои route groups |
| Тесты завязаны на старые namespaces | Перенос тестов вместе с модулем; guardrail-тесты обновляются на новые пути |

---

## Согласованный Execution Backlog

| Backlog Block | Приоритет | Items | Область | Promotion rule |
|---------------|-----------|-------|---------|----------------|
| **Backlog F** | P0 | 1, ~~2~~, ~~3~~→39 | Safety: admin transition guard, cart ownership (item 2 closed, item 3 replaced by 39) | First promoted block |
| **Backlog F2** | P0+P1 | ~~43~~, ~~41~~ | Safety refresh: inventory restore on cancel (P0), promotion counter $fillable hardening (P1). Items ~~40~~ (P2 code smell), ~~42~~ (closed after verification) removed from active P0 | Completed; next priority moves to F3 |
| **Backlog F3** | P0+P1 | 77, 78, 79 | Security/auth hardening: token lifecycle, credential policy, auth audit trail | After F2; before G |
| **Backlog G** | P1 | 4, 5, 6→53, ~~7~~, 8 | Money expansion + service contracts (item 7 closed, item 6 expanded to 53) | After Backlog F |
| **Backlog G2** | P1 | 44, 45, 46, 47, 48, 49, 50 | Race conditions, atomicity, state machine hardening, job reliability | After F2 or parallel with G |
| **Backlog G3** | P1 | 51, 52, 53, 54 | Event consistency, cart throttle, expanded service interfaces, middleware perf | After G |
| **Backlog G4** | P1 | 55, 56, 57 | Frontend safety: auth token, guest cart register, idempotency key | After G2 or independent |
| **Backlog G5** | P1 | 80, 81 | Security boundary hardening: sensitive fillable fields + transport security baseline (co-promote item 41 if still open) | After F3 |
| **Backlog H** | P1 | 9, 10, ~~11~~ | Domain deepening: state machine, logging (item 11 closed) | After Backlog G or parallel |
| **Backlog I** | P2 | 12, 13, 14, 15 | Validation hardening | After Backlog F |
| **Backlog I2** | P2 | 58, 59, 60, 61, 62, 63, 64, 65 | Backend layer hygiene: N+1, float precision, handler DTO, auth duplication, webhook parity, LIKE escape | After G2 |
| **Backlog I3** | P2 | 40, 66, 67, 68, 69, 70, 71, 72, 73 | Type safety и consistency: redundant Hash::make (40), final/readonly, enum predicates, Money comparison, exception hierarchy, ApiResponse | Independent |
| **Backlog I4** | P2 | 82, 83 | Data protection at rest + security guardrails (should absorb open items 52 and 65 if still open) | After G5 or parallel with I2 |
| **Backlog J** | P2 | 16, 17, 18, 19 | Testing expansion | After Backlog I |
| **Backlog K** | P2 | 20, 21, 22 | Frontend polish | Independent |
| **Backlog K2** | P2 | 74, 75, 76 | Frontend infrastructure: auth race, 404 route, storage keys | After G4 or independent |
| **Backlog L** | P2 | 23, 24, 25, 26, 27 | Infrastructure and caching | Independent |
| **Backlog M** | P3 | 28, 36, 37, 38 | Modular monolith: Shared Kernel + Catalog module + guardrails | After G + H + L(25) |
| **Backlog N** | P3 | 29, 30 | Modular monolith: Cart + Users/Auth modules | After Backlog M |
| **Backlog O** | P3 | 31, 32 | Modular monolith: Orders + Payments modules | After Backlog N |
| **Backlog P** | P3 | 33, 34, 35 | Modular monolith: Checkout + Webhooks + Admin distribution | After Backlog O |

## Recommended Promotion Order

1. Complete Backlog E items 21/22 (currently in progress)
2. Promote **Backlog F** + **Backlog F2** (P0 safety: admin guard, cart ownership, inventory restore, counter hardening)
3. Promote **Backlog F3** (P0/P1 security: token lifecycle + auth hardening + audit trail)
4. Promote **Backlog G** + **Backlog G2** (P1 money + race conditions + atomicity)
5. Promote **Backlog G3** + **Backlog G4** (P1 event consistency + frontend safety)
6. Promote **Backlog G5** (P1 security boundary hardening: sensitive fillable + transport security)
7. Promote **Backlog H** (P1 domain deepening)
8. Promote **Backlog I** + **Backlog I2** (P2 validation + backend layer hygiene) — can parallel with H
9. Promote **Backlog I3** (P2 type safety / consistency) — independent
10. Promote **Backlog I4** (P2 security depth: data protection + guardrails)
11. Promote **Backlog J** (P2 testing) — after I stabilizes
12. Promote **Backlog K** + **Backlog K2** (P2 frontend polish + infrastructure)
13. Promote **Backlog L** (P2 infra) — item 25 is gate for P3
14. Promote **Backlog M** (P3 Shared Kernel + Catalog)
15. Promote **Backlog N** (P3 Cart + Users)
16. Promote **Backlog O** (P3 Orders + Payments)
17. Promote **Backlog P** (P3 Checkout + Webhooks + Admin)

**Суммарная оценка (Backlogs F–P):** ~68-80 рабочих дней при последовательном выполнении.

---

## Acceptance Criteria (per promoted block)

- Все quality gates проходят: `composer run lint`, `composer run analyse`, `php artisan test`, `npm run lint`, `npm run lint:ox`, `npm run format:ox:check`, `npm run type-check`, `npm run test`, `npm run build`.
- Архитектурные guardrail-тесты проходят без изменений или с расширением.
- API-контракты сохранены (`/api/v1/*` backward-compatible).
- Перед началом реализации promoted block должен быть явно записан в `docs/ARCHITECTURE_REFACTOR_NEXT.md`.
- После завершения promoted block должны быть обновлены `docs/ARCHITECTURE_REFACTOR_NEXT.md` и `docs/REFACTORING_EXECUTION_PLAN.md`.

## Acceptance Criteria (security blocks — дополнительно)

- `config/sanctum.php` не использует бессрочные API tokens в production-target configuration.
- Все `auth:sanctum` route groups дополнены active-user revalidation middleware там, где bearer token даёт доступ к account/checkout/admin flows.
- Auth endpoints используют explicit login limiter, keyed по identity + IP, с тестируемым lockout-поведением.
- Sensitive privilege/state attributes удалены из `$fillable` либо защищены explicit denylist guardrail-тестом.
- Security audit trail покрывает как минимум `login success`, `login failure`, `logout`, `password reset`.
- Security guardrails покрывают config presence/invariants и не допускают silent regression по items `52`, `65`, `77-83`.

## Acceptance Criteria (P3 modular monolith blocks — дополнительно)

- Namespace перенесённых классов соответствует PSR-4 `App\Domains\{Module}\*`.
- Module ServiceProvider зарегистрирован и содержит все bindings модуля.
- Старые файлы удалены (не дублируются).
- `class_alias` backward compatibility для внешних потребителей добавлен при необходимости.
- Module README обновлён с ownership map, published contracts и external dependencies.
- `ModuleBoundaryGuardrailTest` проходит для перенесённого модуля.
- `SharedKernelStabilityGuardrailTest` проходит.
- `ModuleProviderGuardrailTest` проходит.
- Frontend не затронут (контроллеры сохраняют те же route URIs, фронтенд не знает о backend namespace).
