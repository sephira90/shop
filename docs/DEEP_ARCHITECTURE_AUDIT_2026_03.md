# Deep Architecture Audit & Backlog Alignment

> Date: 2026-03-01
> Status: Aligned backlog input (`Backlog A`, `Backlog B`, `Backlog C` completed; `Backlog D` promoted with items `7`, `8`, `9`, `10` foundation completed; `Backlog E` promoted in incremental mode with item `20` foundation plus checkout/webhook/catalog/hardening/admin-promotion/performance feature-test adoption, item `21` status-transition event+metrics+notification side-effects foundation with config-contract hardening and typed-source boundary hardening, and item `22` docker-compose + docker-ops alias + release-doc parity foundation in active roadmap)
> Source-of-truth for architecture execution: `docs/ARCHITECTURE_REFACTOR_NEXT.md`

## Execution Alignment

1. Этот документ не является вторым execution source-of-truth и не конкурирует с `docs/ARCHITECTURE_REFACTOR_NEXT.md`.
2. Все пункты ниже трактуются как candidate backlog для следующей execution program после закрытия текущего мартовского wave-set.
3. Любой пункт из этого audit становится активной работой только после явного promotion в `docs/ARCHITECTURE_REFACTOR_NEXT.md` как новый wave или backlog block.
4. Группировка ниже приведена в согласованный backlog-порядок: safety and transport first, затем backend boundary hygiene, затем frontend consistency, затем deep domain program, и только потом platform enablement.
5. Пункты `7`, `8`, `9`, `10` и `21` не считаются quick wins. Это отдельные deep refactor slices, которые не должны смешиваться с короткими P0/P1 batch-ами.

## Backlog Promotion Rules

1. Никакой пункт из этого audit не переоткрывает уже завершённые waves `0-24` без отдельного дефекта или regression evidence.
2. P0 safety work должен идти первым promoted block, если backlog будет активирован.
3. Backend quick wins могут идти только после закрытия safety block.
4. Frontend consistency block допускается только после того, как API/contracts для safety block стабилизированы.
5. Deep domain block требует отдельного approval и отдельной execution framing в active roadmap.
6. Platform/testing enablement не должен задерживать P0/P1 risk-reduction work.

## Текущее состояние проекта

Проект — production-ready e-commerce API монолит на Laravel 12 + Vue 3 (Pinia, Vue Router, Tailwind v4). Архитектура CQRS с DTO, тонкими контроллерами, развитой системой guardrail-тестов, PHPStan level 10, Psalm, CI/CD pipeline, smoke-тестами и observability.

Проект находится на высоком уровне зрелости. Ниже — найденные проблемы и направления усиления, сгруппированные по приоритету.

---

## Сводка по приоритетам

| Приоритет | Кол-во | Область |
|-----------|--------|---------|
| **P0** | 5 | Data safety, race conditions, валидация, frontend auth |
| **P1 backend** | 10 | Domain model, архитектурные границы, policies, exception handling |
| **P1 frontend** | 4 | Дублирование, type safety, coupling |
| **P2** | 3 | Фабрики, domain events, docker-compose |

---

## P0 — Data Safety и Race Conditions

### 1. Race condition в CartMutationService

**Проблема:** `CartMutationService::upsertItem()` вызывает `updateOrCreate` на `CartItem` без `lockForUpdate` на Cart. При параллельных запросах возможна гонка по `line_total` и `quantity`.

**Файл:** `app/Services/Cart/CartMutationService.php`

**Решение:** Обернуть upsert в транзакцию с `lockForUpdate` на Cart, аналогично `mergeGuestCart`. Добавить feature-тест на параллельный upsert.

---

### 2. Отсутствие lockForUpdate на Order в webhook transition appliers

**Проблема:** `PaymentWebhookTransitionApplier` обращается к `$payment->order` без блокировки. Параллельные webhook-вызовы могут привести к потерянным обновлениям Order.

**Файлы:**

- `app/Services/Payment/PaymentWebhookTransitionApplier.php` (строка ~55)
- `app/Services/Shipping/ShippingWebhookTransitionApplier.php` (строка ~56)

**Решение:** Добавить `Order::lockForUpdate()->findOrFail($payment->order_id)` в обоих appliers перед обновлением Order.

---

### 3. Race condition в WebhookProcessingPipeline

**Проблема:** `WebhookReceipt::firstOrCreate` выполняется без lock, затем идёт повторная выборка с `lockForUpdate`. Между этими операциями возможна гонка.

**Файл:** `app/Services/Webhook/WebhookProcessingPipeline.php` (строка ~49)

**Решение:** Использовать `DB::transaction` с `lockForUpdate()->firstOrCreate` атомарно, или переключиться на upsert + unique constraint как primary guard.

---

### 4. Отсутствие Form Request для pay()

**Проблема:** `CheckoutController::pay()` использует сырой `Request` без FormRequest. Idempotency-Key формируется как fallback `'pay-'.$order->id` без валидации.

**Файл:** `app/Http/Controllers/Api/V1/CheckoutController.php` (строка ~49-57)

**Решение:** Создать `InitiatePaymentRequest` с обязательным `Idempotency-Key` header. Это соответствует паттерну `PlaceOrderRequest` и `EnsureIdempotencyKeyMiddleware`.

---

### 5. Отсутствие response interceptor для 401/403 (frontend)

**Проблема:** `api/client.ts` не имеет response interceptor. При истекшем токене пользователь видит сырую ошибку вместо автоматического logout и редиректа.

**Файл:** `resources/js/api/client.ts`

**Решение:** Добавить response interceptor: при 401 — вызвать `authStore.logout()` + `router.push('/auth')`. При 403 — показать уведомление. Это стандартный паттерн для SPA с token-based auth.

---

## P1 — Архитектурные границы и Domain Model

### 6. Централизация обработки DomainException

**Проблема:** Каждый контроллер индивидуально ловит `DomainException` и возвращает 422. Это дублирование и нарушение DRY.

**Файлы:** `CheckoutController`, `CartController`, и потенциально все будущие контроллеры.

**Решение:** Перенести обработку `DomainException` в `bootstrap/app.php` exception renderer:

```php
->render(function (DomainException $e, Request $request) {
    if ($request->is('api/*')) {
        return ApiResponse::error($e->getMessage(), 422);
    }
})
```

Удалить try-catch из контроллеров, сделав их ещё тоньше.

---

### 7. Введение Value Object для Money

**Проблема:** Цены хранятся и передаются как `float`. Это классический источник ошибок округления в e-commerce. `line_total`, `subtotal`, `discount_amount`, `shipping_total`, `total` — всё `float`.

**Файлы:** модели `Order`, `OrderItem`, `Price`, сервисы `CheckoutDiscountResolver`, `CheckoutOrderWriter`.

**Решение:** Создать `App\Domain\ValueObjects\Money` (final readonly, integer cents + currency). Использовать в DTO и сервисах. Миграция данных не нужна — конвертация на уровне маппинга.

---

### 8. Выделение Domain Layer

**Проблема:** Явного Domain-слоя нет. Бизнес-логика распределена между Models (например, `Order::hasCapturedPayment()`, `Order::normalizedPaymentStatus()`) и Services. Это размывает границы.

**Решение:**

- Создать `app/Domain/` с value objects (`Money`, `OrderNumber`, `Address`), domain events, и transition policies
- Перенести `hasCapturedPayment()`, `normalizedPaymentStatus()` из Model в domain service / specification
- TransitionPolicy классы (`PaymentStatusTransitionPolicy`, `ShipmentStatusTransitionPolicy`, `OrderStatusTransitionPolicy`) — в `app/Domain/Policies/`
- Enums оставить в `app/Enums/` как shared (они уже не содержат логики)

---

### 9. PlaceCheckoutOrderHandler — слишком много ответственностей

**Проблема:** Handler выполняет: слияние гостевой корзины, резолв корзины для checkout, размещение заказа, инициацию платежа. Это 4 разные операции в одном handler.

**Файл:** `app/Application/Checkout/Commands/PlaceCheckoutOrderHandler.php`

**Решение:** Разделить на шаги через `CheckoutSaga` / `CheckoutOrchestrator` в сервисном слое, оставив handler тонким вызовом оркестратора. Или хотя бы вынести merge cart в отдельный pre-step.

---

### 10. Hardcoded shippingTotal = 0.0

**Проблема:** В `CheckoutService::placeOrder()` стоимость доставки захардкожена как `0.0`.

**Файл:** `app/Services/Checkout/CheckoutService.php` (строка ~70)

**Решение:** Ввести `ShippingCostResolver` / `ShippingCalculator` с интерфейсом. Текущая реализация `FreeShippingCalculator` возвращает 0, но контракт готов к расширению.

---

### 11. Stateless Policy-классы не final readonly

**Проблема:** `PaymentStatusTransitionPolicy`, `ShipmentStatusTransitionPolicy` не помечены `final readonly`, хотя не имеют состояния.

**Файлы:**

- `app/Services/Payment/PaymentStatusTransitionPolicy.php`
- `app/Services/Shipping/ShipmentStatusTransitionPolicy.php`

**Решение:** Пометить `final readonly class`. Тривиальное изменение.

---

### 12. CacheController без authorize()

**Проблема:** `CacheController::refreshCatalog()` не вызывает `$this->authorize()`. Защита только через route middleware `role:admin,manager`.

**Файл:** `app/Http/Controllers/Api/V1/Admin/CacheController.php`

**Решение:** Добавить policy или Gate check. Двойная защита — принцип defense-in-depth.

---

### 13. CouponPolicy — неполная матрица

**Проблема:** `CouponPolicy` содержит только `update`. Нет `viewAny`, `view`, `create`, `delete`.

**Файл:** `app/Policies/CouponPolicy.php`

**Решение:** Дополнить полную матрицу, согласованную с PromotionPolicy (купоны управляются через промоакции, но контракт должен быть полным).

---

### 14. Inconsistent DomainException handling в CartController

**Проблема:** `upsertItem` ловит `DomainException`, а `removeItem` — нет. Поведение при ошибке различается.

**Файл:** `app/Http/Controllers/Api/V1/CartController.php`

**Решение:** Решится при централизации DomainException (пункт 6). Либо добавить catch в `removeItem` как interim fix.

---

### 15. CheckoutDiscountResolver — хрупкая работа с Eloquent

**Проблема:** `getRawOriginal('expires_at')` — прямое обращение к raw attribute Eloquent. Хрупко при изменении кастинга.

**Файл:** `app/Services/Checkout/CheckoutDiscountResolver.php` (строка ~38)

**Решение:** Использовать `$promotion->expires_at` напрямую (после каста это `Carbon|null`), либо вынести проверку валидности промоакции в отдельный specification/method на модели.

---

## P1 — Frontend Consistency

### 16. Дублирование assertion primitives

**Проблема:** `isRecord`, `requireString`, `requireNumber`, `parseNullableString`, `requireBoolean` повторяются в каждом assertion-файле в `contracts/api/v1/assertions/`.

**Файлы:** все файлы в `resources/js/contracts/api/v1/assertions/`

**Решение:** Вынести в `@/contracts/api/v1/assertions/primitives.ts` и реэкспортировать. Сократит дублирование на ~200 строк.

---

### 17. Дублирование RoleName, toSingleQueryValue, address mapping

**Проблемы:**

- `RoleName` определён в `router/index.ts` и `stores/auth.ts`
- `toSingleQueryValue` дублируется в `queries/catalog.ts` вместо импорта из `queries/route-query.ts`
- Маппинг адреса дублируется в `mappers/admin/orders.ts` и `mappers/account/orders.ts`

**Решение:**

- `RoleName` — вынести в `@/types/auth.ts`
- `toSingleQueryValue` — импортировать из `@/queries/route-query.ts`
- Address mapping — вынести в `@/mappers/common.ts` (файл уже существует, но пуст)

---

### 18. isResultSuccess по строке в checkout

**Проблема:** `resultMessage.value.startsWith("Order created")` — хрупкая эвристика.

**Файл:** `resources/js/composables/checkout/useCheckoutPageViewModel.ts`

**Решение:** Ввести явный `ref<'idle' | 'success' | 'error'>` для состояния результата. Текстовое сообщение оставить отдельно.

---

### 19. localStorage coupling в stores

**Проблема:** `authStore` и `cartStore` напрямую обращаются к `localStorage`. Затрудняет тестирование и SSR-совместимость.

**Файлы:** `resources/js/stores/auth.ts`, `resources/js/stores/cart.ts`

**Решение:** Создать `@/utils/storage.ts` с injectable-адаптером (аналогично `CheckoutGuestTokenStorageAdapter`). В тестах использовать in-memory реализацию.

---

## P2 — Инфраструктура и тестирование

### 20. Расширение фабрик

**Проблема:** Существует только `UserFactory`. Тесты полагаются на `CatalogSeeder` для создания Product, Variant, Inventory, Price. Это делает тесты хрупкими и связанными с сидером.

**Решение:** Создать фабрики:

- `ProductFactory`, `ProductVariantFactory`
- `OrderFactory`, `OrderItemFactory`
- `CartFactory`, `CartItemFactory`
- `PromotionFactory`, `CouponFactory`

Это позволит писать изолированные unit/feature тесты без зависимости от seeders.

---

### 21. Domain Events beyond OrderPlaced

**Проблема:** Единственный domain event — `OrderPlaced`. Нет событий для: изменения статуса платежа, изменения статуса доставки, отмены заказа. Side effects (уведомления, метрики) при этих переходах захардкожены в сервисах.

**Решение:** Ввести domain events:

- `PaymentStatusChanged`
- `ShipmentStatusChanged`
- `OrderStatusChanged`

Диспатчить через `afterCommit`. Подписчики: уведомления, метрики, аудит-лог.

---

### 22. Отсутствие docker-compose для local dev

**Проблема:** Есть `Dockerfile`, но нет `docker-compose.yml`. CI использует MySQL + Redis, а локально — SQLite. Это создаёт расхождение поведения.

**Решение:** Создать `docker-compose.yml` с MySQL 8.4, Redis 7, PHP 8.4-fpm. Или использовать Laravel Sail (уже в dev-зависимостях).

---

## Архитектурная схема: текущее состояние vs целевое

### Текущее состояние

```
Controllers → Handlers → Services → Models/Eloquent
                      → Repositories → Models/Eloquent
                      
Services ←→ Models (business logic leak: hasCapturedPayment, normalizedPaymentStatus)
```

### Целевое состояние

```
Controllers → Handlers → Services → Domain Layer → Value Objects
                                                  → Transition Policies
                                                  → Domain Events
                      → Repositories → Models/Eloquent
```

---

## Согласованный Execution Backlog

Ниже — не новый roadmap, а promotion-ready backlog map относительно `docs/ARCHITECTURE_REFACTOR_NEXT.md`.

| Backlog Block | Приоритет | Пункты | Область | Promotion rule |
|---------------|-----------|--------|---------|----------------|
| **Backlog A** | P0 | 1, 2, 3, 4, 5 | Data safety, race conditions, transport validation | Promoted and completed on `2026-03-01`; see active roadmap progress item `23` |
| **Backlog B** | P1 backend | 6, 11, 12, 13, 14, 15 | Backend boundary hygiene / quick wins | Promoted and completed on `2026-03-02`; see active roadmap progress item `24` |
| **Backlog C** | P1 frontend | 16, 17, 18, 19 | Frontend consistency and decoupling | Promoted and completed on `2026-03-02`; see active roadmap progress item `25` |
| **Backlog D** | P1 backend | 7, 8, 9, 10 | Deep domain program | Promoted as incremental deep-domain slice on `2026-03-03`; foundation slices for items `8/9/10` and checkout-scoped foundation for item `7` completed by `2026-03-04` |
| **Backlog E** | P2 | 20, 21, 22 | Platform enablement, factories, domain-event expansion | Promoted in incremental mode on `2026-03-04`: item `20` factory foundation + checkout/webhook/catalog/hardening/admin-promotion/performance feature migration completed; item `21` status-transition event+metrics+notification side-effects foundation with config-contract and typed-source hardening in progress; item `22` docker-compose foundation plus docker-ops alias and release-doc parity started |

## Mapping To Active Roadmap

1. `Backlog A-C` являются естественным продолжением уже закреплённых architectural strengths из `docs/ARCHITECTURE_REFACTOR_NEXT.md`: transport purity, guarded transitions, explicit boundaries, frontend composable discipline.
2. `Backlog D` является расширением архитектуры, а не устранением критического долга. Его нельзя подавать как continuation quick wins.
3. `Backlog E` является enablement/foundation work для будущих refactor loops, но не должен подменять собой safety-driven execution order.
4. При promotion в active roadmap эти блоки должны быть переименованы в новые waves active файла, а numbering должен определяться там, а не здесь.

## Recommended Promotion Order

1. `Backlog A` уже promoted и completed как safety slice.
2. `Backlog B` уже promoted и completed как boundary hygiene slice.
3. `Backlog C` уже promoted и completed как frontend consistency slice.
4. `Backlog D` promoted in incremental mode: checkout orchestration/shipping-resolver foundation (`9`, `10`), payment-status domain extraction (`8`), and checkout-scoped `Money` value-object foundation (`7`) are completed; further expansion of `Money` usage can now be planned as an optional follow-up slice.
5. `Backlog E` promoted in incremental mode with item `20` completed, item `21` in progress (event+metrics+notification slices plus config-contract and typed-source hardening), and item `22` docker-compose + docker-ops alias + release-doc parity foundation started; continue hardening item `21/22` slices before reassessing new platform additions.

---

## Acceptance Criteria (per promoted block)

- Все quality gates проходят: `composer run lint`, `composer run analyse`, `php artisan test`, `npm run lint`, `npm run lint:ox`, `npm run format:ox:check`, `npm run type-check`, `npm run test`, `npm run build`.
- Архитектурные guardrail-тесты (tests/Unit/Architecture/) проходят без изменений или с расширением.
- API-контракты сохранены (`/api/v1/*` backward-compatible).
- Перед началом реализации promoted block должен быть явно записан в `docs/ARCHITECTURE_REFACTOR_NEXT.md`.
- После завершения promoted block должны быть обновлены `docs/ARCHITECTURE_REFACTOR_NEXT.md` и `docs/REFACTORING_EXECUTION_PLAN.md`.
