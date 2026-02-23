# Template Componentization Plan

## Цель

Декомпозировать шаблоны страниц (`resources/js/pages/**/*.vue`) на переиспользуемые и тестируемые компоненты без изменения бизнес-поведения, API-контрактов и роутинга.

## Область

- In scope: разбиение page templates на feature/ui компоненты, выравнивание props/emits контрактов, обновление тестов.
- Out of scope: изменение бизнес-логики composables/queries, редизайн UI, изменение API/DTO, изменение ролей/ACL.

## Текущее состояние (инвентарь страниц)

| Страница | Общие строки | Строки template | Приоритет |
|---|---:|---:|---|
| `resources/js/pages/admin/AdminProductsPage.vue` | 93 | 39 | done/P0 |
| `resources/js/pages/admin/AdminOrdersPage.vue` | 104 | 54 | done/P0 |
| `resources/js/pages/admin/AdminCategoriesPage.vue` | 75 | 30 | done/P0 |
| `resources/js/pages/AccountOrdersPage.vue` | 93 | 58 | done/P1 |
| `resources/js/pages/AccountProfilePage.vue` | 70 | 36 | done/P1 |
| `resources/js/pages/CheckoutPage.vue` | 119 | 33 | done/P2 |
| `resources/js/pages/CartPage.vue` | 62 | 18 | done/P2 |
| `resources/js/pages/AuthPage.vue` | 122 | 30 | done/P2 |
| `resources/js/pages/CatalogPage.vue` | 44 | 25 | done/P2 |
| `resources/js/pages/ProductPage.vue` | 42 | 21 | done/P2 |
| `resources/js/pages/admin/AdminDashboardPage.vue` | 42 | 6 | done/P3 |
| `resources/js/pages/HomePage.vue` | 21 | 9 | done/P3 |
| `resources/js/pages/admin/AdminPromotionsPage.vue` | 92 | 40 | done/low |

Примечание: `AdminPromotionsPage` уже декомпозирована на компоненты (`resources/js/components/admin/promotions/*`) и выступает референсом целевого состояния.

## Архитектурные правила декомпозиции

1. Страница (`pages/*`) остается контейнером orchestration-уровня:
   - подключает composable;
   - передает данные/коллбеки вниз;
   - не содержит массивный HTML-блок.
2. Feature-компоненты не ходят в API и не знают про router/store напрямую.
3. Все side-effects и mutation flow остаются в composables/view-model.
4. Контракты компонентов строго типизированы (`props`, `emits`).
5. Декомпозиция идет по UX-блокам, а не по “атомам ради атомов”.
6. Минимальная цель после декомпозиции:
   - `template` страницы <= 120 строк;
   - отдельный компонент <= 180 строк (кроме сложных таблиц, где допустимо до 240).

## Целевая структура директорий

```text
resources/js/components/
  account/
    AccountTabsNav.vue
    orders/
    profile/
  catalog/
  cart/
  checkout/
  auth/
  admin/
    dashboard/
    products/
    orders/
    categories/
    promotions/   # уже есть
  ui/
    AppActionsRow.vue
    AppButton.vue
    AppCard.vue
    AppCheckboxField.vue
    AppDateTimeInput.vue
    AppEnumSelect.vue
    AppFilterSelect.vue
    AppFormField.vue
    AppFormLayout.vue
    AppGridThreeColumns.vue
    AppGridTwoColumns.vue
    AppMutedText.vue
    AppNotice.vue
    AppNumberInput.vue
    AppEmptyState.vue
    AppPaginationBar.vue
    AppSearchInput.vue
    AppSelectInput.vue
    AppSectionTitle.vue
    AppStackBetween.vue
    AppSubmitResetActions.vue
    AppTableSection.vue
    AppTextareaInput.vue
    AppTextInput.vue
```

Правило: сначала извлекаем feature-компоненты домена, затем при появлении дублирования выносим в `components/ui`.
Правило дублирования: если UI-блок используется `>=2` раз, выносить в shared-компонент (`components/ui`) или domain-shared компонент.

## Пошаговый план реализации

## Wave 1 (P0): Admin core templates

### 1.1 `AdminProductsPage`

Извлечь:
- `AdminProductsHeaderActions.vue`
- `AdminProductFormCard.vue`
- `AdminProductMainFields.vue`
- `AdminProductVariantsSection.vue`
- `AdminProductVariantCard.vue`
- `AdminProductSeoFields.vue`
- `AdminProductsTableCard.vue`
- `AdminProductsFiltersBar.vue`
- `AdminProductsPaginationBar.vue`

Критерии:
- page template <= 120 строк;
- все callbacks идут через существующий `useAdminProducts` API;
- текущие flows (`submit/edit/delete/toggle visibility/cache refresh`) без регрессий.

### 1.2 `AdminOrdersPage`

Извлечь:
- `AdminOrdersHeaderCard.vue`
- `AdminOrdersMetricsRow.vue`
- `AdminOrdersFiltersBar.vue`
- `AdminOrdersTableCard.vue`
- `AdminOrdersPaginationBar.vue`
- `AdminOrderDetailsPanel.vue`
- `AdminOrderStatusEditor.vue`

Критерии:
- сохранены URL-sync и pagination;
- статусные chips/formatters продолжают использоваться из существующего view-model.

### 1.3 `AdminCategoriesPage`

Извлечь:
- `AdminCategoriesFormCard.vue`
- `AdminCategoriesListCard.vue`
- `AdminCategoriesFiltersBar.vue`
- `AdminCategoriesTable.vue`
- `AdminCategoriesPaginationBar.vue`

Критерии:
- сохранены flows `startEdit/remove/reset/submit`;
- route-sync поведение и фильтры без изменений.

## Статус выполнения (2026-02-22)

- `Wave 1.1 AdminProductsPage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/admin/products/*`.
- `Wave 1.2 AdminOrdersPage`: выполнено, добавлены компоненты `resources/js/components/admin/orders/*`, сохранены route-sync, pagination и status update flow.
- `Wave 1.3 AdminCategoriesPage`: выполнено, добавлены `resources/js/components/admin/categories/*`, сохранены route-sync фильтры/pagination и mutation-flows.
- Добавлен component-level contract suite для `Wave 1`:
  - `resources/js/tests/components/admin/admin-component-contracts.spec.ts`.
- `Wave 2.1 AccountOrdersPage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/account/orders/*`.
- `Wave 2 shared`: добавлен и подключен `resources/js/components/account/AccountTabsNav.vue` для `AccountOrdersPage` и `AccountProfilePage`.
- Добавлен component-level contract suite для account orders:
  - `resources/js/tests/components/account/account-orders-component-contracts.spec.ts`.
- `Wave 2.2 AccountProfilePage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/account/profile/*`.
- Добавлен component-level contract suite для account profile:
  - `resources/js/tests/components/account/account-profile-component-contracts.spec.ts`.
- `Wave 3.1 CatalogPage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/catalog/*`.
- Добавлен component-level contract suite для catalog:
  - `resources/js/tests/components/catalog/catalog-component-contracts.spec.ts`.
- `Wave 3.2 ProductPage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/product/*`.
- Добавлен component-level contract suite для product page:
  - `resources/js/tests/components/product/product-component-contracts.spec.ts`.
- `Wave 3.3 CartPage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/cart/*`.
- Добавлен component-level contract suite для cart page:
  - `resources/js/tests/components/cart/cart-component-contracts.spec.ts`.
- `Wave 3.4 CheckoutPage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/checkout/*`.
- Добавлен component-level contract suite для checkout page:
  - `resources/js/tests/components/checkout/checkout-component-contracts.spec.ts`.
- `Wave 3.5 AuthPage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/auth/*`.
- Добавлен component-level contract suite для auth page:
  - `resources/js/tests/components/auth/auth-component-contracts.spec.ts`.
- `Wave 4.1 HomePage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/home/*`.
- Добавлен component-level contract suite для home page:
  - `resources/js/tests/components/home/home-component-contracts.spec.ts`.
- `Wave 4.1 AdminDashboardPage`: выполнено, страница переведена в orchestration-only с вынесением UI-блоков в `resources/js/components/admin/dashboard/*`.
- Добавлен component-level contract suite для admin dashboard:
  - `resources/js/tests/components/admin/admin-dashboard-component-contracts.spec.ts`.
- `Wave 4.2 Shared UI extraction`: выполнено, добавлены и интегрированы shared primitives:
  - `resources/js/components/ui/AppNotice.vue`
  - `resources/js/components/ui/AppEmptyState.vue`
  - `resources/js/components/ui/AppPaginationBar.vue`.
- Выполнена миграция повторов `notice/empty/pagination` при пороге `>=2` использования в storefront/account/admin компонентах.
- Добавлен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.3 Shared status/badge primitives`: выполнено, добавлены и интегрированы:
  - `resources/js/components/ui/AppStatusChip.vue`
  - `resources/js/components/ui/AppBadge.vue`.
- Выполнена миграция повторов `status-chip/badge` при пороге `>=2` использования в account/admin компонентах.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.4 Shared layout helpers`: выполнено, добавлены и интегрированы:
  - `resources/js/components/ui/AppMetricCard.vue`
  - `resources/js/components/ui/AppDetailBox.vue`.
- Выполнена миграция повторов `metric-card/order-detail-box` при пороге `>=2` использования в account/admin компонентах.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.5 Shared status stack`: выполнено, добавлен и интегрирован:
  - `resources/js/components/ui/AppStatusStack.vue`.
- Выполнена миграция повторов status-групп в order-компонентах:
  - `resources/js/components/account/orders/AccountOrderCard.vue`
  - `resources/js/components/admin/orders/AdminOrdersTableCard.vue`
  - `resources/js/components/admin/orders/AdminOrderDetailsPanel.vue`.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.6 Shared table section wrapper`: выполнено, добавлен и интегрирован:
  - `resources/js/components/ui/AppTableSection.vue`.
- Выполнена миграция повторов `table-wrap/table` блоков (`>=2` использования):
  - `resources/js/components/admin/orders/AdminOrdersTableCard.vue`
  - `resources/js/components/admin/orders/AdminOrderDetailsPanel.vue`
  - `resources/js/components/admin/products/AdminProductsListCard.vue`
  - `resources/js/components/admin/categories/AdminCategoriesTable.vue`
  - `resources/js/components/admin/promotions/PromotionCampaignTable.vue`
  - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`
  - `resources/js/components/account/orders/AccountOrderDetailsTable.vue`
  - `resources/js/components/cart/CartItemsTable.vue`.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.7 Shared actions/stack wrappers`: выполнено, добавлены и интегрированы:
  - `resources/js/components/ui/AppActionsRow.vue`
  - `resources/js/components/ui/AppStackBetween.vue`.
- Выполнена миграция повторов `actions/actions--top` и `stack stack--between` блоков (`>=2` использования):
  - `CatalogFiltersCard.vue`, `CartItemsTable.vue`, `AccountProfileSummaryCard.vue`
  - `AdminCategoriesFiltersBar.vue`, `AdminOrderStatusEditor.vue`, `AccountOrdersFiltersBar.vue`, `AdminOrdersFiltersBar.vue`
  - `PromotionCampaignTable.vue`, `AdminProductsListCard.vue`
  - `CartSummaryHeader.vue`, `AppPaginationBar.vue`, `PromotionCampaignForm.vue`
  - `AccountOrderCard.vue`, `AdminProductsFormCard.vue`, `AuthModeSwitcher.vue`
  - `AdminCategoriesListCard.vue`, `AdminCategoriesFormCard.vue`, `AccountOrdersHeaderCard.vue`
  - `AdminOrderDetailsPanel.vue`, `AdminOrdersHeaderCard.vue`.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.8 Shared form/grid layout wrappers`: выполнено, добавлены и интегрированы:
  - `resources/js/components/ui/AppFormLayout.vue`
  - `resources/js/components/ui/AppGridTwoColumns.vue`.
- Выполнена миграция повторов `form-grid actions--top` и `grid grid-2` блоков (`>=2` использования):
  - `resources/js/components/account/profile/AccountProfileFormCard.vue`
  - `resources/js/components/admin/categories/AdminCategoriesFormCard.vue`
  - `resources/js/components/admin/products/AdminProductsFormCard.vue`
  - `resources/js/components/admin/promotions/PromotionCampaignForm.vue`
  - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`
  - `resources/js/components/admin/products/AdminProductVariantCard.vue`
  - `resources/js/components/account/orders/AccountOrderDetailsTable.vue`
  - `resources/js/components/admin/orders/AdminOrderDetailsPanel.vue`
  - `resources/js/components/auth/AuthRegisterForm.vue`
  - `resources/js/pages/ProductPage.vue`
  - `resources/js/pages/CheckoutPage.vue`
  - `resources/js/pages/AuthPage.vue`
  - `resources/js/pages/AccountProfilePage.vue`.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.9 Shared grid-3 + action-group standardization`: выполнено:
  - добавлен `resources/js/components/ui/AppGridThreeColumns.vue`;
  - завершена миграция raw `div.actions` блоков на `resources/js/components/ui/AppActionsRow.vue` в card/form/table секциях.
- Выполнена миграция повторов `grid grid-3` и action-group блоков (`>=2` использования):
  - `resources/js/components/home/HomeKpiGrid.vue`
  - `resources/js/components/catalog/CatalogProductGrid.vue`
  - `resources/js/components/admin/dashboard/AdminDashboardNavGrid.vue`
  - `resources/js/components/admin/orders/AdminOrderDetailsPanel.vue`
  - `resources/js/components/admin/orders/AdminOrderStatusEditor.vue`
  - `resources/js/components/admin/products/AdminProductVariantCard.vue`
  - `resources/js/components/account/orders/AccountOrderCard.vue`
  - `resources/js/components/account/profile/AccountHeroCard.vue`
  - `resources/js/components/account/profile/AccountProfileFormCard.vue`
  - `resources/js/components/admin/categories/AdminCategoriesFormCard.vue`
  - `resources/js/components/admin/categories/AdminCategoriesTable.vue`
  - `resources/js/components/admin/products/AdminProductsFormCard.vue`
  - `resources/js/components/admin/products/AdminProductsListCard.vue`
  - `resources/js/components/admin/promotions/PromotionCampaignForm.vue`
  - `resources/js/components/admin/promotions/PromotionCampaignTable.vue`
  - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`
  - `resources/js/components/cart/CartQuantityControl.vue`
  - `resources/js/components/ui/AppPaginationBar.vue`.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.10 Shared field/checkbox + submit-reset wrappers`: выполнено:
  - добавлены:
    - `resources/js/components/ui/AppFormField.vue`
    - `resources/js/components/ui/AppCheckboxField.vue`
    - `resources/js/components/ui/AppSubmitResetActions.vue`.
- Выполнена миграция повторов `field` / `checkbox-field` / submit-reset action-pairs (`>=2` использования):
  - `resources/js/components/account/profile/AccountProfileFormCard.vue`
  - `resources/js/components/admin/categories/AdminCategoriesFormCard.vue`
  - `resources/js/components/admin/products/AdminProductsFormCard.vue`
  - `resources/js/components/admin/promotions/PromotionCampaignForm.vue`
  - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`
  - `resources/js/components/admin/products/AdminProductVariantCard.vue`
  - `resources/js/components/admin/orders/AdminOrderStatusEditor.vue`.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.11 Shared section/muted text wrappers`: выполнено:
  - добавлены:
    - `resources/js/components/ui/AppSectionTitle.vue`
    - `resources/js/components/ui/AppMutedText.vue`.
- Выполнена миграция повторов `section-title` / `muted` (`>=2` использования):
  - `resources/js/components/account/orders/AccountOrderCard.vue`
  - `resources/js/components/account/orders/AccountOrderDetailsTable.vue`
  - `resources/js/components/account/orders/AccountOrdersHeaderCard.vue`
  - `resources/js/components/account/profile/AccountProfileFormCard.vue`
  - `resources/js/components/account/profile/AccountProfileSummaryCard.vue`
  - `resources/js/components/admin/dashboard/AdminDashboardNavCard.vue`
  - `resources/js/components/admin/categories/AdminCategoriesFormCard.vue`
  - `resources/js/components/admin/orders/AdminOrderDetailsPanel.vue`
  - `resources/js/components/admin/orders/AdminOrdersHeaderCard.vue`
  - `resources/js/components/admin/orders/AdminOrdersTableCard.vue`
  - `resources/js/components/admin/products/AdminProductsFormCard.vue`
  - `resources/js/components/admin/products/AdminProductsListCard.vue`
  - `resources/js/components/admin/promotions/PromotionCampaignForm.vue`
  - `resources/js/components/admin/promotions/PromotionCampaignTable.vue`
  - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`
  - `resources/js/components/auth/AuthModeSwitcher.vue`
  - `resources/js/components/cart/CartSummaryHeader.vue`
  - `resources/js/components/catalog/CatalogProductCard.vue`
  - `resources/js/components/catalog/CatalogFiltersCard.vue`
  - `resources/js/components/checkout/CheckoutHeader.vue`
  - `resources/js/components/product/ProductInfoCard.vue`
  - `resources/js/components/product/ProductPurchaseCard.vue`.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.12 Shared card/button wrappers`: выполнено:
  - добавлены:
    - `resources/js/components/ui/AppCard.vue`
    - `resources/js/components/ui/AppButton.vue`.
- Выполнена миграция повторов `card` / `btn btn-*` (`>=2` использования):
  - `resources/js/pages/AuthPage.vue`
  - `resources/js/pages/CheckoutPage.vue`
  - `resources/js/pages/CartPage.vue`
  - `resources/js/pages/AccountOrdersPage.vue`
  - `resources/js/pages/admin/AdminOrdersPage.vue`
  - `resources/js/components/AppHeader.vue`
  - `resources/js/components/home/HomeHeroSection.vue`
  - `resources/js/components/home/HomeKpiGrid.vue`
  - `resources/js/components/auth/AuthModeSwitcher.vue`
  - `resources/js/components/auth/AuthLoginForm.vue`
  - `resources/js/components/auth/AuthRegisterForm.vue`
  - `resources/js/components/cart/CartItemsTable.vue`
  - `resources/js/components/cart/CartQuantityControl.vue`
  - `resources/js/components/catalog/CatalogFiltersCard.vue`
  - `resources/js/components/catalog/CatalogProductCard.vue`
  - `resources/js/components/checkout/CheckoutAddressCard.vue`
  - `resources/js/components/product/ProductInfoCard.vue`
  - `resources/js/components/product/ProductPurchaseCard.vue`
  - `resources/js/components/account/profile/AccountProfileFormCard.vue`
  - `resources/js/components/account/profile/AccountProfileSummaryCard.vue`
  - `resources/js/components/account/orders/AccountOrderCard.vue`
  - `resources/js/components/account/orders/AccountOrdersHeaderCard.vue`
  - `resources/js/components/admin/dashboard/AdminDashboardNavCard.vue`
  - `resources/js/components/admin/categories/AdminCategoriesFormCard.vue`
  - `resources/js/components/admin/categories/AdminCategoriesListCard.vue`
  - `resources/js/components/admin/categories/AdminCategoriesTable.vue`
  - `resources/js/components/admin/orders/AdminOrderDetailsPanel.vue`
  - `resources/js/components/admin/orders/AdminOrdersHeaderCard.vue`
  - `resources/js/components/admin/orders/AdminOrdersTableCard.vue`
  - `resources/js/components/admin/orders/AdminOrderStatusEditor.vue`
  - `resources/js/components/admin/products/AdminProductsFormCard.vue`
  - `resources/js/components/admin/products/AdminProductsListCard.vue`
  - `resources/js/components/admin/products/AdminProductVariantCard.vue`
  - `resources/js/components/admin/products/AdminProductVariantsSection.vue`
  - `resources/js/components/admin/promotions/PromotionCampaignForm.vue`
  - `resources/js/components/admin/promotions/PromotionCampaignTable.vue`
  - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`
  - `resources/js/components/ui/AppPaginationBar.vue`.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`.
- `Wave 4.13 Card-composition hardening + KPI domain wrapper`: выполнено:
  - `inCard/wrapInCard` card-toggle переведен на композицию с `AppCard` в shared UI:
    - `resources/js/components/ui/AppEmptyState.vue`
    - `resources/js/components/ui/AppMetricCard.vue`
    - `resources/js/components/ui/AppPaginationBar.vue`.
- Добавлен domain-shared wrapper для KPI-card паттерна (`>=2` использования внутри home-grid):
  - `resources/js/components/home/HomeKpiCard.vue`;
  - `resources/js/components/home/HomeKpiGrid.vue` переведен на `HomeKpiCard`.
- Расширен component-level contract suite для shared UI:
  - `resources/js/tests/components/ui/ui-component-contracts.spec.ts` (добавлен кейс `AppPaginationBar` + `wrapInCard`).
- `Wave 4.14 Shared tone/variant API + AppButton transport hardening`: выполнено:
  - унифицированы `tone/variant` API в shared UI с обратной совместимостью:
    - `resources/js/components/ui/AppStatusChip.vue` (`tone`, fallback `toneClass`)
    - `resources/js/components/ui/AppBadge.vue` (`tone`, fallback `toneClass`)
    - `resources/js/components/ui/AppMetricCard.vue` (`variant`, fallback `soft`)
    - `resources/js/components/ui/AppStatusStack.vue` (`tone` passthrough);
  - переведены статические tone-usage на новые props:
    - `resources/js/components/admin/promotions/PromotionCampaignTable.vue`
    - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`
    - `resources/js/components/admin/categories/AdminCategoriesTable.vue`
    - `resources/js/components/account/profile/AccountHeroCard.vue`
    - `resources/js/components/account/orders/AccountOrdersMetricsRow.vue`
    - `resources/js/components/admin/orders/AdminOrdersMetricsRow.vue`
    - `resources/js/components/admin/orders/AdminOrderDetailsPanel.vue`;
  - усилен `AppButton` как единый transport-layer для `button/a/router-link`:
    - `resources/js/components/ui/AppButton.vue` (auto `as` resolve via `to/href`, safe `rel` for `_blank`, disabled-link semantics с `aria-disabled/tabindex` и click-guard, attrs passthrough);
  - расширен component-level contract suite для shared UI:
    - `resources/js/tests/components/ui/ui-component-contracts.spec.ts` (edge-cases `AppButton` links + tone/variant coverage).
- `Wave 4.15 AppButton router-link migration + typed presenter rollout`: выполнено:
  - завершена миграция `AppButton` с `:as="RouterLink"` на упрощенный `to` API (удален лишний `RouterLink` boilerplate):
    - `resources/js/components/catalog/CatalogProductCard.vue`
    - `resources/js/components/cart/CartItemsTable.vue`
    - `resources/js/components/home/HomeHeroSection.vue`
    - `resources/js/components/account/profile/AccountProfileSummaryCard.vue`;
  - введен typed presenter-layer для статусов/бейджей (без передачи raw CSS-class строк из page/composable уровня):
    - `resources/js/utils/order-presentation.ts` (`order/payment/shipment/verification` tone mappers + `productStatusBadgeTone`);
    - `resources/js/components/account/orders/AccountOrderCard.vue`
    - `resources/js/components/admin/orders/AdminOrdersTableCard.vue`
    - `resources/js/components/admin/orders/AdminOrderDetailsPanel.vue`
    - `resources/js/components/account/profile/AccountHeroCard.vue`
    - `resources/js/components/admin/products/AdminProductsListCard.vue`;
  - view-model/page wiring переведены на `tone`-контракты:
    - `resources/js/composables/account/orders/useAccountOrdersViewModel.ts`
    - `resources/js/composables/admin/orders/useAdminOrdersViewModel.ts`
    - `resources/js/composables/account/profile/useAccountProfileQuery.ts`
    - `resources/js/composables/account/profile/useAccountProfileViewModel.ts`
    - `resources/js/composables/admin/products/useAdminProductsViewModel.ts`
    - `resources/js/pages/AccountOrdersPage.vue`
    - `resources/js/pages/admin/AdminOrdersPage.vue`
    - `resources/js/pages/AccountProfilePage.vue`
    - `resources/js/pages/admin/AdminProductsPage.vue`;
  - обновлены contract/unit tests под новый migration-path:
    - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`
    - `resources/js/tests/components/account/account-orders-component-contracts.spec.ts`
    - `resources/js/tests/components/account/account-profile-component-contracts.spec.ts`
    - `resources/js/tests/components/admin/admin-component-contracts.spec.ts`
    - `resources/js/tests/utils/order-presentation.spec.ts`.
- `Wave 4.16 Semantic filter inputs extraction`: выполнено:
  - добавлены shared UI-примитивы смыслового уровня для list/filter UX:
    - `resources/js/components/ui/AppSearchInput.vue`
    - `resources/js/components/ui/AppFilterSelect.vue`;
  - выполнена миграция повторов filter-input/select блоков (`>=2` использования):
    - `resources/js/components/account/orders/AccountOrdersFiltersBar.vue`
    - `resources/js/components/admin/orders/AdminOrdersFiltersBar.vue`
    - `resources/js/components/admin/categories/AdminCategoriesFiltersBar.vue`
    - `resources/js/components/admin/promotions/PromotionCampaignTable.vue`
    - `resources/js/components/catalog/CatalogFiltersCard.vue`
    - `resources/js/components/admin/products/AdminProductsListCard.vue`;
  - расширен component-level contract suite shared UI:
    - `resources/js/tests/components/ui/ui-component-contracts.spec.ts` (покрытие `AppSearchInput` и `AppFilterSelect`).
- `Wave 4.17 Semantic form inputs extraction`: выполнено:
  - добавлены shared UI-примитивы form-ввода:
    - `resources/js/components/ui/AppTextInput.vue`
    - `resources/js/components/ui/AppNumberInput.vue`
    - `resources/js/components/ui/AppDateTimeInput.vue`
    - `resources/js/components/ui/AppTextareaInput.vue`;
  - выполнена миграция повторов text/number/datetime/textarea полей (`>=2` использования):
    - `resources/js/components/admin/products/AdminProductsFormCard.vue`
    - `resources/js/components/admin/products/AdminProductVariantCard.vue`
    - `resources/js/components/admin/categories/AdminCategoriesFormCard.vue`
    - `resources/js/components/admin/promotions/PromotionCampaignForm.vue`
    - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`
    - `resources/js/components/account/profile/AccountProfileFormCard.vue`
    - `resources/js/components/auth/AuthLoginForm.vue`
    - `resources/js/components/auth/AuthRegisterForm.vue`
    - `resources/js/components/checkout/CheckoutContactFields.vue`
    - `resources/js/components/checkout/CheckoutAddressCard.vue`;
  - `AppNumberInput` получил поддержку `v-model.number` для совместимости с числовыми form-state сценариями;
  - расширен component-level contract suite shared UI:
    - `resources/js/tests/components/ui/ui-component-contracts.spec.ts` (покрытие `AppTextInput`, `AppNumberInput`, `AppDateTimeInput`, `AppTextareaInput`).
- `Wave 4.18 Semantic select inputs extraction`: выполнено:
  - добавлены shared UI-примитивы для select-flow:
    - `resources/js/components/ui/AppSelectInput.vue`
    - `resources/js/components/ui/AppEnumSelect.vue`;
  - выполнена миграция повторов enum/dynamic select блоков (`>=2` использования):
    - `resources/js/components/admin/products/AdminProductsFormCard.vue`
    - `resources/js/components/admin/categories/AdminCategoriesFormCard.vue`
    - `resources/js/components/admin/promotions/PromotionCampaignForm.vue`
    - `resources/js/components/admin/orders/AdminOrderStatusEditor.vue`
    - `resources/js/components/product/ProductPurchaseCard.vue`;
  - `resources/js/components/ui/AppFilterSelect.vue` переведен на композицию через `AppSelectInput`;
  - расширен component-level contract suite shared UI:
    - `resources/js/tests/components/ui/ui-component-contracts.spec.ts` (покрытие `AppSelectInput`, `AppEnumSelect`).
- `Wave 4.19 Semantic checkbox + quantity/read-only hardening`: выполнено:
  - добавлены shared UI-примитивы:
    - `resources/js/components/ui/AppCheckboxInput.vue`
    - `resources/js/components/ui/AppQuantityInput.vue`;
  - выполнена миграция raw checkbox input (`>=2` использования) на semantic wrapper:
    - `resources/js/components/admin/categories/AdminCategoriesFormCard.vue`
    - `resources/js/components/admin/products/AdminProductsFormCard.vue`
    - `resources/js/components/admin/products/AdminProductVariantCard.vue`
    - `resources/js/components/admin/promotions/PromotionCampaignForm.vue`
    - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`;
  - закрыт read-only/disabled path в profile form через shared input:
    - `resources/js/components/account/profile/AccountProfileFormCard.vue`;
  - cart quantity flow переведен на специализированный semantic input:
    - `resources/js/components/cart/CartQuantityControl.vue`;
  - расширен component-level contract suite shared UI:
    - `resources/js/tests/components/ui/ui-component-contracts.spec.ts` (покрытие `AppCheckboxInput`, `AppQuantityInput`).
- `Wave 4.20 Semantic form-shell extraction`: выполнено:
  - добавлен shared UI-примитив:
    - `resources/js/components/ui/AppFormShell.vue`;
  - выполнена миграция повторов `form class="grid actions--top"` (`>=2` использования):
    - `resources/js/components/auth/AuthLoginForm.vue`
    - `resources/js/components/auth/AuthRegisterForm.vue`
    - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`
    - `resources/js/pages/CheckoutPage.vue`;
  - расширен component-level contract suite shared UI:
    - `resources/js/tests/components/ui/ui-component-contracts.spec.ts` (покрытие `AppFormShell`).
- `Wave 4.21 Shared form/input edge-case contracts`: выполнено:
  - усилено component-level контрактное покрытие shared form-shell/input wrappers:
    - `resources/js/components/ui/AppFormShell.vue`
    - `resources/js/components/ui/AppTextInput.vue`
    - `resources/js/components/ui/AppNumberInput.vue`
    - `resources/js/components/ui/AppDateTimeInput.vue`
    - `resources/js/components/ui/AppTextareaInput.vue`
    - `resources/js/components/ui/AppSelectInput.vue`;
  - в `resources/js/tests/components/ui/ui-component-contracts.spec.ts` добавлены edge-case сценарии:
    - `attrs passthrough` на root/native элементы;
    - проверка `disabled/readonly` для form/input wrappers;
    - проверка `AppFormShell` без `actions--top`.
- `Wave 4.22 Checkbox/quantity edge-case contracts + radio inventory`: выполнено:
  - подтверждена инвентаризация `radio/fieldset` по `resources/js/**/*.{vue,ts}`:
    - повторов не найдено, `AppRadioInput/AppRadioGroup` остается отложенным до порога `>=2`;
  - усилено component-level покрытие edge-cases для:
    - `resources/js/components/ui/AppCheckboxInput.vue`
    - `resources/js/components/ui/AppQuantityInput.vue`;
  - в `resources/js/tests/components/ui/ui-component-contracts.spec.ts` добавлены сценарии:
    - `attrs passthrough` и model binding для `AppCheckboxInput`;
    - `attrs passthrough`, `disabled/readonly`, integer/boundary normalization для `AppQuantityInput`.
- `Wave 4.23 Search/filter/enum edge-case contracts`: выполнено:
  - усилено component-level покрытие edge-cases для:
    - `resources/js/components/ui/AppSearchInput.vue`
    - `resources/js/components/ui/AppFilterSelect.vue`
    - `resources/js/components/ui/AppEnumSelect.vue`;
  - в `resources/js/tests/components/ui/ui-component-contracts.spec.ts` добавлены сценарии:
    - `AppSearchInput`: `attrs passthrough`, `disabled` contract, emit только по `keyup.enter`;
    - `AppFilterSelect`: `attrs passthrough`, `disabled` на nested select, change/update model flow;
    - `AppEnumSelect`: `attrs passthrough`, `disabled` на nested select, options mapping и change/update model flow.
- `Wave 4.24 Field/actions edge-case contracts`: выполнено:
  - усилено component-level покрытие edge-cases для:
    - `resources/js/components/ui/AppFormField.vue`
    - `resources/js/components/ui/AppSubmitResetActions.vue`
    - `resources/js/components/ui/AppActionsRow.vue`;
  - в `resources/js/tests/components/ui/ui-component-contracts.spec.ts` добавлены сценарии:
    - `AppFormField`: `attrs passthrough` на label-root + slot stability для mixed content;
    - `AppSubmitResetActions`: `withTopSpacing` contract + attrs passthrough на actions-root;
    - `AppActionsRow`: attrs/class passthrough + default layout contract без `actions--top`.
- `Wave 4.25 Table primitives and order-items unification`: выполнено:
  - добавлены shared UI table-примитивы:
    - `resources/js/components/ui/AppTableActionsCell.vue`
    - `resources/js/components/ui/AppTableEmptyStateRow.vue`
    - `resources/js/components/ui/BooleanStatusChip.vue`;
  - добавлен domain-shared компонент order-line таблицы:
    - `resources/js/components/orders/OrderItemsTable.vue`;
  - выполнена миграция повторов table/actions/boolean-status/empty-state (`>=2` использования):
    - `resources/js/components/admin/orders/AdminOrdersTableCard.vue`
    - `resources/js/components/admin/products/AdminProductsListCard.vue`
    - `resources/js/components/admin/categories/AdminCategoriesTable.vue`
    - `resources/js/components/admin/promotions/PromotionCampaignTable.vue`
    - `resources/js/components/admin/promotions/PromotionCouponsPanel.vue`;
  - объединена разметка order-items таблиц через shared domain-компонент:
    - `resources/js/components/account/orders/AccountOrderDetailsTable.vue`
    - `resources/js/components/admin/orders/AdminOrderDetailsPanel.vue`;
  - расширено contract-покрытие:
    - `resources/js/tests/components/ui/ui-component-contracts.spec.ts`
    - `resources/js/tests/components/orders/order-items-table-component-contracts.spec.ts`.
- `Wave 4.26 UI folder normalization`: выполнено:
  - плоский слой `resources/js/components/ui/*.vue` разложен по смысловым подпапкам:
    - `actions/`
    - `forms/`
    - `layout/`
    - `feedback/`
    - `data-display/`
    - `table/`
    - `typography/`;
  - обновлены импорты на новый путь-namespace по всему frontend-коду:
    - `resources/js/components/**/*.vue`
    - `resources/js/pages/**/*.vue`
    - `resources/js/tests/components/**/*.spec.ts`;
  - добавлена документация структуры:
    - `resources/js/components/ui/README.md`;
  - behavior/UI-контракты сохранены, изменения ограничены структурой и import-paths.

## Wave 2 (P1): Account templates

### 2.1 `AccountOrdersPage`

Извлечь:
- `AccountTabsNav.vue` (shared между profile/orders)
- `AccountOrdersHeaderCard.vue`
- `AccountOrdersMetricsRow.vue`
- `AccountOrdersFiltersBar.vue`
- `AccountOrderCard.vue`
- `AccountOrderDetailsTable.vue`
- `AccountOrdersPaginationCard.vue`

### 2.2 `AccountProfilePage`

Извлечь:
- `AccountHeroCard.vue`
- `AccountMetricsGrid.vue`
- `AccountProfileFormCard.vue`
- `AccountProfileSummaryCard.vue`
- переиспользовать `AccountTabsNav.vue`.

Критерии для Wave 2:
- существующие tests для account composables green;
- добавлены component-level тесты для критичных rendering/emit контрактов.

## Wave 3 (P2): Storefront templates

### 3.1 `CatalogPage`

Извлечь:
- `CatalogFiltersCard.vue`
- `CatalogProductGrid.vue`
- `CatalogProductCard.vue`
- `CatalogEmptyState.vue`

### 3.2 `ProductPage`

Извлечь:
- `ProductInfoCard.vue`
- `ProductPurchaseCard.vue`

### 3.3 `CartPage`

Извлечь:
- `CartSummaryHeader.vue`
- `CartItemsTable.vue`
- `CartQuantityControl.vue`
- `CartEmptyState.vue`

### 3.4 `CheckoutPage`

Извлечь:
- `CheckoutHeader.vue`
- `CheckoutContactFields.vue`
- `CheckoutAddressCard.vue` (две инстанции: billing/shipping)
- `CheckoutResultNotice.vue`

### 3.5 `AuthPage`

Извлечь:
- `AuthHeroCard.vue`
- `AuthModeSwitcher.vue`
- `AuthLoginForm.vue`
- `AuthRegisterForm.vue`

Критерии для Wave 3:
- UX не меняется;
- `add to cart`, checkout submit и auth redirect flows не ломаются.

## Wave 4 (P3): Remaining low-complexity pages and unification

### 4.1 `HomePage` и `AdminDashboardPage`

Извлечь:
- `HomeHeroSection.vue`
- `HomeKpiGrid.vue`
- `AdminDashboardHero.vue`
- `AdminDashboardNavGrid.vue`
- `AdminDashboardNavCard.vue`

### 4.2 Shared UI extraction (только при подтвержденном дублировании)

Вынести в `components/ui`:
- `AppNotice.vue`
- `AppEmptyState.vue`
- `AppPaginationBar.vue`

Критерий:
- минимум 2 использования (или 2+ повтора в разных компонентах), иначе оставляем domain-level компонент.

## Технический workflow для каждой страницы

1. Выделить блоки в template (header/filters/table/form/pagination/details).
2. Сначала извлечь read-only блоки (минимальный риск).
3. Затем извлечь interactive блоки (emit events вверх).
4. Подключить строгие типы props/emits.
5. Обновить/добавить tests:
   - component render/emit tests;
   - существующие integration tests.
6. Прогнать quality gates перед merge.

## Тестовая стратегия

- Component tests: `resources/js/tests/components/**`.
- Existing integration suites не ослаблять:
  - `use-admin-server-list-flows.spec.ts`
  - `use-admin-mutation-flows.spec.ts`
  - `use-account-orders.spec.ts`
  - `use-account-profile.spec.ts`
- Добавить smoke assertions на отсутствие регрессий рендера:
  - кнопки действий;
  - notice states;
  - pagination controls;
  - empty states.

## Quality gates на каждый batch

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
- `php artisan app:observability-alert-check`
- `php artisan app:oncall-drill-smoke`

## Стратегия коммитов

1. `refactor(admin-products-ui): extract products page blocks into components`
2. `refactor(admin-orders-ui): extract orders page blocks into components`
3. `refactor(admin-categories-ui): extract categories page blocks into components`
4. `refactor(account-ui): extract account profile/orders template blocks`
5. `refactor(storefront-ui): extract catalog/cart/checkout/auth/product components`
6. `refactor(ui-shared): introduce shared notice/empty/pagination primitives`
7. `test(frontend): add component-level template decomposition coverage`
8. `docs(frontend): update component map and conventions`

## Definition of Done

- Все страницы разложены на feature-компоненты по плану.
- Ни один page template не содержит “монолитный” HTML-блок.
- Поведение фильтров/таблиц/форм/pagination не изменилось.
- Full quality gate green.
- Изменения зафиксированы в `docs/REFACTORING_EXECUTION_PLAN.md` по batch-логике.
