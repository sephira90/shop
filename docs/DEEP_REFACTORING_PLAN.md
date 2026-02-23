# Deep Refactoring Plan

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

- [ ] Разбить `ui-component-contracts.spec.ts` на доменные тестовые пакеты.
- [ ] Вынести общие mount/helpers/fixtures.

Definition of Done:

- Меньший blast radius при падениях, ускоренный triage.

---

#### P2.3 Performance and ops budgets

- [ ] Добавить budget checks для checkout/cart/admin list flows.
- [ ] Включить regression checks в CI/quality pipeline.

Definition of Done:

- Деградации производительности выявляются автоматически до merge.

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
