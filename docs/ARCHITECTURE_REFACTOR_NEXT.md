# Architecture Refactor Next (Architecture-First)

Date: `2026-03-01`
Status: `Active`
Priority mode: `Architecture-first`

## Execution Authority

1. This file is the active architecture execution source-of-truth.
2. Historical plans in `docs/*PLAN*.md` are archival references only and must not be used as active execution authority.
3. `docs/REFACTORING_EXECUTION_PLAN.md` remains an operational execution log only.

## Summary

DTO migration and the first refactor waves are completed, and the March 1 execution program is now materially closed for the current wave set:

- PHPStan now runs clean at level 10 without `phpstan-baseline.neon`;
- canonical backend static analysis now runs through `composer run analyse` as `PHPStan + Psalm`;
- repository, queued side-effect, and policy completeness guardrails now protect the intended architecture boundaries for the current codebase.
- the first promoted safety slice from `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md` is also closed:
  cart mutation locking, webhook receipt dedupe hardening, checkout pay transport validation, and global SPA `401/403` response handling are now explicit boundaries.
- the next promoted backend boundary-hygiene slice is closed:
  DomainException mapping is centralized in the global API exception renderer, coupon policy completeness is enforced, admin cache refresh now includes policy authorization, and checkout discount datetime checks no longer depend on raw Eloquent attributes.

Goal of this program: close those gaps without breaking `/api/v1/*` response envelope (`data/meta/error`) and with strict quality-gate enforcement after each logical block.

## Architectural Strengths To Preserve

1. Application layer already follows CQRS with typed DTO return boundaries; handlers must not regress to ORM, paginator, or resource return types.
2. API V1 controllers are transport-only shells; business logic must not move back into controllers.
3. Checkout and cart orchestration has already been decomposed into focused collaborators and should evolve by composition, not by re-collapsing into larger services.
4. Admin frontend flows already use decomposed composables and shared mutation pipelines; new work should extend those primitives instead of reintroducing page-local logic.
5. Payment and shipping webhooks already follow a unified ingress -> transition -> orchestration pattern; future changes must preserve parity.
6. Architecture guardrails are a first-class mechanism in this repository; each new boundary introduced by Waves 15-24 must extend guardrail coverage instead of relying on convention only.

## Audit Snapshot (`2026-03-01`)

1. CQRS boundaries are established across the application layer and typed DTO returns are already the dominant contract style.
2. API V1 controllers are consistently thin and transport-focused; no controller currently acts as a business-logic sink.
3. Checkout/cart decomposition is already at orchestration level, with focused collaborators for discounting, idempotency, inventory, writing, and finalization.
4. Frontend admin pages already operate on decomposed filter/list/detail/form/mutation responsibilities with shared mutation pipelines.
5. Webhook processing, transition policies, CI quality gates, and production smoke flows are already materially stronger than typical Laravel baselines.

## Execution Progress

1. Wave 0 completed (governance source-of-truth reset).
2. Wave 1 completed (transport purity + full API V1 controller architecture coverage).
3. Wave 2 completed (webhook contract hardening, parity tests, ingress prevalidation + error taxonomy).
4. Wave 3 completed (filter DTO migration, enum status input contract, scalar-safe `toArray()` boundaries, legacy dead-code guardrails).
5. Wave 4 completed (service decomposition):
   - completed sub-blocks:
     - checkout discount resolution extracted to `CheckoutDiscountResolver`;
     - checkout idempotency decision path extracted to `CheckoutIdempotencyGuard`.
     - checkout inventory allocation extracted to `CheckoutInventoryAllocator`.
     - checkout cart preparation/order write extracted to `CheckoutCartPreparer` + `CheckoutOrderWriter`.
     - checkout residual orchestration split completed:
       - request identity derivation extracted to `CheckoutRequestIdentityResolver`,
       - post-write completion side-effects extracted to `CheckoutOrderFinalizer`,
       with `CheckoutService` reduced to transaction orchestration only.
     - payment webhook decomposition completed:
       - ingress extraction/validation delegated to `PaymentWebhookIngressResolver`,
       - transition persistence + post-capture side-effects delegated to `PaymentWebhookTransitionApplier`,
       with `PaymentWebhookAdapter` reduced to webhook orchestration only.
     - shipping webhook decomposition completed:
       - ingress extraction/validation delegated to `ShippingWebhookIngressResolver`,
       - transition persistence + timestamp/order-state application delegated to `ShippingWebhookTransitionApplier`,
       with `ShippingWebhookAdapter` reduced to webhook orchestration only.
     - cart decomposition completed via explicit boundaries:
       - `CartResolver`,
       - `CartMutationService`,
       - `CartResultMapper`,
       with `CartService` reduced to facade orchestration.
     - status transition policies extracted to dedicated classes:
       - `PaymentStatusTransitionPolicy`,
       - `ShipmentStatusTransitionPolicy`,
       - `OrderStatusTransitionPolicy`,
       with matrix unit guardrails.
     - admin/manual order status update flow now adopts transition policies with transport-safe `422` mapping.
6. Wave 5 completed (application boundary hardening):
   - completed sub-blocks:
     - checkout payment initiation command handler migrated from ORM model return to typed DTO boundary (`CheckoutPaymentResultDto`);
     - architecture guardrail added for checkout command handlers: no ORM model return types.
     - admin categories application handlers migrated from ORM/paginator return types to typed DTO boundaries:
       - `AdminCategoryResultDto`,
       - `AdminCategoryPaginatedResultDto`,
       with transport response mapped through explicit `data/meta` DTO arrays.
     - architecture guardrail added for admin category command/query handlers: no ORM/paginator return types.
     - admin products application handlers migrated from ORM/paginator return types to typed DTO boundaries:
       - `AdminProductResultDto`,
       - `AdminProductPaginatedResultDto`,
       with explicit category/variant/inventory typed result boundaries.
     - architecture guardrail added for admin product command/query handlers: no ORM/paginator return types.
     - admin promotions application handlers migrated from ORM/paginator return types to typed DTO boundaries:
       - `AdminPromotionResultDto`,
       - `AdminPromotionCouponResultDto`,
       - `AdminPromotionPaginatedResultDto`,
       with explicit promotion/coupon typed result boundaries and DTO-driven `data/meta` mapping.
     - architecture guardrail added for admin promotion command/query handlers: no ORM/paginator return types.
     - admin orders application handlers migrated from ORM/paginator return types to typed DTO boundaries:
       - `AdminOrderSummaryResultDto`,
       - `AdminOrderDetailResultDto`,
       - `AdminOrderPaginatedResultDto`,
       with explicit order item/payment/shipment typed result boundaries and DTO-driven `data/meta` mapping.
     - architecture guardrail added for admin order command/query handlers: no ORM/paginator return types.
     - catalog application query handlers migrated from ORM/paginator/collection return types to typed DTO boundaries:
       - `CatalogProductResultDto`,
       - `CatalogProductPaginatedResultDto`,
       - `CatalogCategoriesResultDto`,
       with DTO-driven `data/meta` mapping and transport mapping detached from `ProductResource`.
     - checkout application query boundary hardened:
       - `PaginateMyOrdersHandler` migrated from paginator return to `CheckoutOrderPaginatedResultDto`,
       - checkout order payload mapped through `CheckoutOrderResultDto`,
       with DTO-driven `data/meta` mapping and transport mapping detached from `OrderResource`.
     - architecture guardrail generalized for application layer:
       - `tests/Unit/Architecture/ApplicationHandlerBoundaryTest` enforces no ORM/paginator/Eloquent collection return types for `app/Application/*Handler`.
     - auth persistence/query responsibilities moved behind repository contracts:
       - `App\Application\Auth\Contracts\AuthUserRepository`,
       - `App\Application\Auth\Contracts\AuthPasswordBrokerRepository`,
       with infrastructure implementations:
       - `App\Repositories\AuthUserRepository`,
       - `App\Repositories\AuthPasswordBrokerRepository`.
     - auth application handlers migrated to repository contracts for persistence-sensitive flows:
       - register/login/logout/profile update/email verification/password reset.
     - architecture guardrail added for auth application handlers:
       - `tests/Unit/Architecture/ApplicationAuthRepositoryBoundaryTest` enforces:
         - no direct `User::query(...)` usage in auth handlers;
         - no direct `Password::sendResetLink(...)` / `Password::reset(...)` usage in auth handlers;
         - required repository contract dependencies in auth handlers.
7. Wave 6 completed (frontend structural consolidation):
   - completed sub-blocks:
     - duplicated admin-list route-query logic extracted into shared schema-driven helper:
       - `resources/js/queries/admin/route-query-schema.ts`;
     - admin route-query modules migrated to schema helper:
       - `resources/js/queries/admin/categories.ts`,
       - `resources/js/queries/admin/promotions.ts`,
       - `resources/js/queries/admin/products.ts`,
       - `resources/js/queries/admin/orders.ts`;
     - deterministic unit coverage added for shared helper:
       - `resources/js/tests/queries/admin/route-query-schema.spec.ts`.
     - duplicated admin route-sync pagination orchestration extracted into shared loader:
       - `resources/js/composables/admin/adminRouteSync.ts` (`useAdminRouteSyncedLoader`);
     - admin query composables migrated to shared route-sync loader:
       - `resources/js/composables/admin/categories/useAdminCategoriesQuery.ts`,
       - `resources/js/composables/admin/promotions/useAdminPromotionsQuery.ts`,
       - `resources/js/composables/admin/products/useAdminProductsQuery.ts`,
       - `resources/js/composables/admin/orders/useAdminOrdersQuery.ts`;
     - deterministic unit coverage added for route-sync loader:
       - `resources/js/tests/composables/admin/admin-route-sync.spec.ts`.
     - admin order detail loading hardened against out-of-order responses:
       - `resources/js/composables/admin/orders/useAdminOrdersQuery.ts` now guards stale detail responses with request-id and abort-aware flow.
     - shared abort/cancel error helper extracted and reused:
       - `resources/js/composables/requestError.ts` (`isAbortLikeError`);
       - reused in `resources/js/composables/useServerPaginatedList.ts` and `useAdminOrdersQuery.ts`.
     - deterministic race coverage added for admin orders detail selection:
       - `resources/js/tests/composables/use-admin-server-list-flows.spec.ts` verifies stale detail response cannot overwrite newer selection.
     - admin orders query decomposed by detail-state responsibility:
       - extracted `resources/js/composables/admin/orders/useAdminOrderDetailsState.ts` for selected-order state, draft state, and detail request lifecycle;
       - `resources/js/composables/admin/orders/useAdminOrdersQuery.ts` reduced to list/filter orchestration and delegates detail/draft concerns to detail-state module.
     - deterministic unit coverage added for extracted detail-state module:
       - `resources/js/tests/composables/admin/use-admin-order-details-state.spec.ts`.
     - admin orders query decomposed by derived-state responsibility:
       - extracted `resources/js/composables/admin/orders/useAdminOrdersDerivedState.ts` for list-derived projections:
         - `filteredOrders`,
         - `selectedOrderSummary`,
         - `paidCount` / `completedCount` / `pendingPaymentCount`;
       - `resources/js/composables/admin/orders/useAdminOrdersQuery.ts` now composes derived-state module instead of inlining aggregation logic.
     - deterministic unit coverage added for derived-state module:
       - `resources/js/tests/composables/admin/use-admin-orders-derived-state.spec.ts`.
     - admin orders query decomposed by filter-state responsibility:
       - extracted `resources/js/composables/admin/orders/useAdminOrdersFilterState.ts` for:
         - default/route-initialized filter state,
         - filter-source tuple for debounce watchers,
         - list param builder,
         - parsed-route apply/read helpers;
       - `resources/js/composables/admin/orders/useAdminOrdersQuery.ts` now composes filter-state module and no longer inlines filter policy.
     - deterministic unit coverage added for filter-state module:
       - `resources/js/tests/composables/admin/use-admin-orders-filter-state.spec.ts`.
     - admin orders query decomposed by list-state responsibility:
       - extracted `resources/js/composables/admin/orders/useAdminOrdersListState.ts` for:
         - server paginated list orchestration;
         - route-sync loader binding;
         - notice lifecycle and detail-state callbacks on success/error.
       - `resources/js/composables/admin/orders/useAdminOrdersQuery.ts` now composes `useAdminOrdersListState(...)` and no longer inlines list loading policy.
     - deterministic unit coverage added for list-state module:
       - `resources/js/tests/composables/admin/use-admin-orders-list-state.spec.ts`.
     - admin promotions query decomposed by filter/list/selection responsibilities:
       - extracted `resources/js/composables/admin/promotions/useAdminPromotionsFilterState.ts`;
       - extracted `resources/js/composables/admin/promotions/useAdminPromotionsSelectionState.ts`;
       - extracted `resources/js/composables/admin/promotions/useAdminPromotionsListState.ts`;
       - `resources/js/composables/admin/promotions/useAdminPromotionsQuery.ts` now composes dedicated modules instead of inlining route hydration, selection fallback, and list-load flow.
     - deterministic unit coverage added for promotions decomposition:
       - `resources/js/tests/composables/admin/use-admin-promotions-filter-state.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-promotions-selection-state.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-promotions-list-state.spec.ts`.
     - admin categories query decomposed by filter/list responsibilities:
       - extracted `resources/js/composables/admin/categories/useAdminCategoriesFilterState.ts`;
       - extracted `resources/js/composables/admin/categories/useAdminCategoriesListState.ts`;
       - `resources/js/composables/admin/categories/useAdminCategoriesQuery.ts` now composes dedicated modules instead of inlining route hydration and list-load flow.
     - deterministic unit coverage added for categories decomposition:
       - `resources/js/tests/composables/admin/use-admin-categories-filter-state.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-categories-list-state.spec.ts`.
     - admin products query decomposed by filter/list/category-loader responsibilities:
       - extracted `resources/js/composables/admin/products/useAdminProductsFilterState.ts`;
       - extracted `resources/js/composables/admin/products/useAdminProductsListState.ts`;
       - extracted `resources/js/composables/admin/products/useAdminProductCategoriesState.ts`;
       - `resources/js/composables/admin/products/useAdminProductsQuery.ts` now composes dedicated modules instead of inlining route hydration, list-load flow, and category-options loading loop.
     - deterministic unit coverage added for products decomposition:
       - `resources/js/tests/composables/admin/use-admin-products-filter-state.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-products-list-state.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-product-categories-state.spec.ts`.
     - deterministic integration coverage extended for products server list flow:
       - `resources/js/tests/composables/use-admin-server-list-flows.spec.ts` now verifies:
         - first-page reload on search change;
         - route-sync query/page parity for products.
     - admin products mutations decomposed by form/crud/publishing responsibilities:
       - extracted `resources/js/composables/admin/products/useAdminProductFormState.ts` for:
         - edit form lifecycle,
         - variant form add/remove policy,
         - product-to-form mapping (including normalized datetime and variant attributes formatting).
       - extracted `resources/js/composables/admin/products/useAdminProductCrudMutations.ts` for:
         - create/update flow,
         - guarded delete flow with role check and confirm adapter,
         - deterministic page fallback after deleting last item on page.
       - extracted `resources/js/composables/admin/products/useAdminProductPublishingMutations.ts` for:
         - visibility toggle mutation,
         - catalog-cache refresh mutation.
       - `resources/js/composables/admin/products/useAdminProductsMutations.ts` reduced to composition-only façade over dedicated mutation modules.
     - deterministic unit coverage added for products mutation decomposition:
       - `resources/js/tests/composables/admin/use-admin-product-form-state.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-product-crud-mutations.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-product-publishing-mutations.spec.ts`.
     - admin promotions mutations decomposed by form/coupon-form/crud/coupon responsibilities:
       - extracted `resources/js/composables/admin/promotions/useAdminPromotionFormState.ts` for:
         - promotion edit form lifecycle and normalization mapping;
         - selected-promotion synchronization during edit flow.
       - extracted `resources/js/composables/admin/promotions/useAdminPromotionCouponFormState.ts` for coupon form lifecycle.
       - extracted `resources/js/composables/admin/promotions/useAdminPromotionCrudMutations.ts` for:
         - create/update campaign mutation flow;
         - delete campaign flow with deterministic page fallback.
       - extracted `resources/js/composables/admin/promotions/useAdminPromotionCouponMutations.ts` for:
         - create coupon flow;
         - coupon activation toggle flow.
       - `resources/js/composables/admin/promotions/useAdminPromotionsMutations.ts` reduced to composition-only facade over dedicated modules.
     - deterministic unit coverage added for promotions mutation decomposition:
       - `resources/js/tests/composables/admin/use-admin-promotion-form-state.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-promotion-coupon-form-state.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-promotion-crud-mutations.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-promotion-coupon-mutations.spec.ts`.
     - admin categories mutations decomposed by form/crud responsibilities:
       - extracted `resources/js/composables/admin/categories/useAdminCategoryFormState.ts` for:
         - category edit form lifecycle;
         - form hydration/reset policy.
       - extracted `resources/js/composables/admin/categories/useAdminCategoryCrudMutations.ts` for:
         - create/update category mutation flow;
         - guarded delete flow with deterministic page fallback.
       - `resources/js/composables/admin/categories/useAdminCategoriesMutations.ts` reduced to composition-only facade over dedicated modules.
     - deterministic unit coverage added for categories mutation decomposition:
       - `resources/js/tests/composables/admin/use-admin-category-form-state.spec.ts`;
       - `resources/js/tests/composables/admin/use-admin-category-crud-mutations.spec.ts`.
     - admin view-model mutation/notice wiring consolidated into shared context:
       - extracted `resources/js/composables/admin/useAdminMutationContext.ts` for:
         - shared `useAdminNotice` + `useAdminMutation` composition;
         - explicit adapters for query-only and mutation flows.
       - migrated admin view-models to shared context:
         - `resources/js/composables/admin/categories/useAdminCategoriesViewModel.ts`;
         - `resources/js/composables/admin/promotions/useAdminPromotionsViewModel.ts`;
         - `resources/js/composables/admin/products/useAdminProductsViewModel.ts`;
         - `resources/js/composables/admin/orders/useAdminOrdersViewModel.ts`.
       - deterministic unit coverage added:
         - `resources/js/tests/composables/admin/use-admin-mutation-context.spec.ts`.
     - admin order status mutation synchronization extracted to dedicated state updater:
       - extracted `resources/js/composables/admin/orders/adminOrderStatusMutationState.ts`;
       - `resources/js/composables/admin/orders/useAdminOrdersMutations.ts` now delegates list/detail/draft synchronization to extracted module.
       - deterministic unit coverage added:
         - `resources/js/tests/composables/admin/use-admin-order-status-mutation-state.spec.ts`.
     - deterministic delete-pagination fallback centralized for admin CRUD flows:
       - extracted `resources/js/composables/admin/adminListPagination.ts` (`resolvePageAfterLastItemRemoval`);
       - reused in:
         - `resources/js/composables/admin/categories/useAdminCategoryCrudMutations.ts`;
         - `resources/js/composables/admin/products/useAdminProductCrudMutations.ts`;
         - `resources/js/composables/admin/promotions/useAdminPromotionCrudMutations.ts`.
       - deterministic unit coverage added:
         - `resources/js/tests/composables/admin/admin-list-pagination.spec.ts`.
     - repeated admin delete mutation flow centralized into shared pipeline:
       - extracted `resources/js/composables/admin/adminDeleteMutationPipeline.ts` for:
         - optional permission gate;
         - confirm adapter integration;
         - pending-state wiring;
         - success + post-delete callback execution.
       - reused in:
         - `resources/js/composables/admin/categories/useAdminCategoryCrudMutations.ts`;
         - `resources/js/composables/admin/products/useAdminProductCrudMutations.ts`;
         - `resources/js/composables/admin/promotions/useAdminPromotionCrudMutations.ts`.
       - deterministic unit coverage added:
         - `resources/js/tests/composables/admin/admin-delete-mutation-pipeline.spec.ts`.
     - route-sync filter hydration hardened against duplicate reloads and first-page regressions:
       - `resources/js/composables/useServerListFilters.ts` now supports guarded reload suppression;
       - `resources/js/composables/admin/adminRouteSync.ts` now suppresses route-applied filter echo reloads by normalized query comparison.
       - deterministic unit coverage expanded:
         - `resources/js/tests/composables/admin/admin-route-sync.spec.ts`.
       - deterministic admin integration coverage expanded:
         - `resources/js/tests/composables/use-admin-server-list-flows.spec.ts` verifies:
           - products route-synced search normalization does not duplicate reload;
           - external route query update does not regress to page `1`.
     - repeated admin create/update submit flow centralized into shared pipeline:
       - extracted `resources/js/composables/admin/adminSubmitMutationPipeline.ts` for:
         - create/update branch selection by editing id;
         - shared success message dispatch;
         - mode-specific and shared success hooks.
       - reused in:
         - `resources/js/composables/admin/categories/useAdminCategoryCrudMutations.ts`;
         - `resources/js/composables/admin/products/useAdminProductCrudMutations.ts`;
         - `resources/js/composables/admin/promotions/useAdminPromotionCrudMutations.ts`.
       - deterministic unit coverage added:
         - `resources/js/tests/composables/admin/admin-submit-mutation-pipeline.spec.ts`.
     - repeated admin ui-effects + mutation-context view-model bootstrap centralized:
       - extracted `resources/js/composables/admin/useAdminUiMutationContext.ts`;
       - reused in:
         - `resources/js/composables/admin/categories/useAdminCategoriesViewModel.ts`;
         - `resources/js/composables/admin/products/useAdminProductsViewModel.ts`;
         - `resources/js/composables/admin/promotions/useAdminPromotionsViewModel.ts`.
       - deterministic unit coverage added:
         - `resources/js/tests/composables/admin/use-admin-ui-mutation-context.spec.ts`.
     - repeated simple-action mutation flow centralized for remaining admin modules:
       - extracted `resources/js/composables/admin/adminActionMutationPipeline.ts` for:
         - pending-state wiring;
         - success message resolution;
         - post-success callback orchestration.
       - reused in:
         - `resources/js/composables/admin/orders/useAdminOrdersMutations.ts`;
         - `resources/js/composables/admin/products/useAdminProductPublishingMutations.ts`;
         - `resources/js/composables/admin/promotions/useAdminPromotionCouponMutations.ts`.
       - deterministic unit coverage added:
         - `resources/js/tests/composables/admin/admin-action-mutation-pipeline.spec.ts`.
    - remaining low-yield residual notice-adapter duplication centralized into shared admin mutation-context contracts.
8. Wave 7 completed (observability modularization):
   - completed sub-blocks:
     - rolling cache metric persistence extracted to `ObservabilityMetricStore`;
     - snapshot aggregation extracted to `ObservabilitySnapshotBuilder`;
     - `ObservabilityService` reduced to ingestion/logging facade over explicit store + snapshot modules;
     - alert routing channel split completed:
       - `ObservabilityAlertCooldownStore` extracted for cooldown suppression policy;
       - `ObservabilityAlertMessageBuilder` extracted for alert subject/body shaping;
       - `ObservabilityAlertRouter` reduced to orchestration over explicit alert channels;
       - channel senders extracted behind shared contract:
         - `EmailObservabilityAlertChannel`,
         - `SlackObservabilityAlertChannel`,
         - `PagerDutyObservabilityAlertChannel`,
       - shared routing-warning logger extracted to `ObservabilityAlertRoutingLogger`;
       - typed alert payload/routing DTOs introduced for internal observability boundaries.

9. Wave 8 completed (operations hardening):
   - completed sub-blocks:
     - cleanup retention resolution extracted to `MaintenanceCleanupRetentionResolver` with typed `MaintenanceCleanupRetentionDto`;
     - on-call drill command plan extracted to `OncallDrillCheckPlanFactory` with typed `OncallDrillCheckDto`;
     - on-call escalation mapping extracted to `OncallDrillEscalationMatrix`;
     - operational docs normalized to active architecture execution source-of-truth;
     - deterministic guardrails added for cleanup validation and on-call plan composition.
10. Wave 9 completed (governance and operational guardrails):
   - completed sub-blocks:
     - operational command execution extracted behind explicit support boundaries:
       - `MaintenanceCleanupExecutor`,
       - `OncallDrillRunner`,
       - `ObservabilityAlertCheckRunner`,
       - shared `ConsoleCommandRunner` for nested command execution.
     - operational commands reduced to orchestration-only shells:
       - `app:maintenance-cleanup`,
       - `app:oncall-drill-smoke`,
       - `app:observability-alert-check`.
     - architecture guardrail added for operational command boundaries:
       - `tests/Unit/Architecture/OperationalCommandBoundaryTest`.
     - docs/config drift guardrails added:
       - `tests/Unit/Architecture/OperationalDocsConfigGuardrailTest`.
     - operational regression coverage expanded for alert fallback without configured channels:
       - `tests/Feature/ObservabilityAlertCheckCommandTest`.
11. Wave 10 completed (observability report command modularization):
   - completed sub-blocks:
     - observability report option parsing extracted to `ObservabilityReportOptionsResolver` with typed `ObservabilityReportOptionsDto`;
     - threshold and required-sample evaluation extracted to `ObservabilityReportThresholdEvaluator` with typed evaluation result boundary;
     - console output shaping extracted to `ObservabilityReportOutputBuilder` with typed output DTO;
     - `ObservabilityReportRunner` introduced as orchestration boundary over snapshot loading + evaluation;
     - `app:observability-report` reduced to orchestration-only shell and added to operational command boundary guardrails;
     - deterministic unit/feature coverage added for JSON output, disabled-observability warning path, option validation, and threshold evaluation.
12. Wave 11 completed (smoke command scenario modularization):
   - completed sub-blocks:
     - `app:api-contract-smoke` reduced to orchestration-only shell over explicit options/context/scenario-registry/runner/output boundaries;
     - `app:webhook-flow-smoke` reduced to orchestration-only shell over typed runner/output boundaries with explicit production rollback or persist policy;
     - `app:performance-smoke` reduced to orchestration-only shell over options resolver, setup factory, scenario registry, profiler, runner, and output builder;
     - performance smoke scenarios split into explicit scenario services for catalog/cart/checkout/admin-orders/admin-products paths;
     - smoke command architecture guardrail added via `tests/Unit/Architecture/SmokeCommandBoundaryTest`;
     - deterministic unit/feature coverage added for scenario selection, budget parsing, failure aggregation, and production rollback or persist modes.
13. Wave 12 completed (shared smoke infrastructure consolidation):
   - completed sub-blocks:
     - shared smoke execution contracts extracted:
       - `SmokeExecutionOptionsDto`,
       - `SmokeCommandOutputDto`,
       - `SmokeExecutionOptionsResolver`,
       - `SmokeRollbackPolicy`,
       - `SmokeScenarioSelector`,
       - `SmokeCommandOutputFactory`;
     - api-contract, webhook-flow, and performance smoke modules migrated onto shared rollback/scenario-selection/output contracts;
     - legacy per-command smoke DTO/options wrappers removed so shared smoke contracts are the single source-of-truth;
     - deterministic contract guardrails added:
       - `tests/Unit/Architecture/SmokeScenarioRegistryContractTest`,
       - `tests/Unit/Architecture/SmokeDocumentationGuardrailTest`,
       - shared smoke unit coverage for execution options, rollback policy, scenario selection, and output factory;
     - README and operational runbook expanded with targeted smoke execution examples for selective scenario isolation and explicit production `--persist` mode.
14. Wave 13 completed (command contract and scheduler guardrails):
   - completed sub-blocks:
     - shared nested command invocation boundary extracted:
       - `ConsoleCommandInvocationDto`,
       - `ObservabilityReportCommandInvocationFactory`,
       with `ObservabilityAlertCheckRunner` and `OncallDrillCheckPlanFactory` sharing the same `app:observability-report` invocation contract;
     - legacy observability alert-check config wrappers removed so alert/on-call nested report execution has one active contract source;
     - command signature architecture guardrail added:
       - `tests/Unit/Architecture/ConsoleCommandSignatureGuardrailTest`,
       covering:
         - required option presence for critical operational and smoke commands;
         - nested command invocation parameters staying aligned with real command signatures;
     - scheduler wiring guardrail added:
       - `tests/Unit/Architecture/OperationalSchedulerWiringGuardrailTest`,
       covering:
         - required scheduled operational commands,
         - expected cron cadence,
         - `withoutOverlapping` policy,
         - prohibition of direct scheduling for write-smokes and `app:observability-report`;
     - deterministic regression coverage expanded for command failure and explicit write-smoke execution paths:
       - unknown `--only` scenarios now fail command-level smoke tests for API contract and performance smoke;
       - on-call drill feature coverage now includes explicit `--with-write-smokes` success path.
15. Wave 14 completed (release and CI guardrails):
   - completed sub-blocks:
     - canonical composer aliases added for release-quality and production-smoke flows:
       - `quality:backend`,
       - `quality:frontend`,
       - `ops:healthcheck`,
       - `ops:production-smoke-core`,
       - `ops:ci-production-smoke`;
     - CI workflow reduced to canonical composer aliases for backend/frontend quality and CI smoke verification;
     - deployment smoke script normalized to canonical production smoke alias instead of ad-hoc curl checks;
     - release-readiness docs normalized to canonical aliases and active roadmap source-of-truth:
       - `README.md`,
       - `docs/PHASE5_RELEASE_READINESS_CHECKLIST.md`;
     - release/CI guardrails added:
       - `tests/Unit/Architecture/ReleaseCommandScriptGuardrailTest`,
       - `tests/Unit/Architecture/ReleaseDocsWorkflowGuardrailTest`.
16. Deep audit completed (`2026-03-01`):
   - full-project architecture audit covering backend (`app/`), frontend (`resources/js/`), tests (`tests/`), CI/DevOps, PHPStan baseline, and configuration;
   - active roadmap reprioritized around four tiers:
     - tier 1 (domain boundary): account orders extraction, category selector decoupling, maintenance cleanup resource strategy, provider boundary split;
     - tier 2 (transport/convention): `CatalogController` inline validation;
     - tier 3 (frontend parity): `formatPrice` 7+ location duplication, missing catalog/checkout/auth composable tests;
     - tier 4 (safety/guardrails): PHPStan 562-line baseline reduction, repository/jobs/policy architecture guardrail expansion;
   - new waves added: Wave 21 (transport validation consistency), Wave 22 (frontend price formatting and utility consolidation), Wave 23 (static analysis hardening), Wave 24 (architecture guardrail expansion);
   - targeted regression checks confirmed current behavior remains green before planning the next execution block:
     - `php artisan test --filter="AppMaintenanceCleanupCommandTest|AccountOrdersApiTest|AdminCategoryCrudTest"`;
     - `npm run test -- resources/js/tests/composables/use-account-orders.spec.ts resources/js/tests/queries/account-orders-query.spec.ts resources/js/tests/queries/admin/categories-query.spec.ts resources/js/tests/composables/admin/use-admin-categories-list-state.spec.ts`.

## Confirmed Findings

1. PHPStan now runs clean at level 10 without a baseline file; static-analysis hardening no longer remains as active roadmap debt for the current execution program.
2. Architecture guardrails now cover repositories, queued side-effect dispatch paths, and policy completeness; the former roadmap gaps for repository/jobs/policy boundary protection are closed.

8. Wave 15 completed (account orders module extraction and read-model split):
   - account-order read transport extracted from `CheckoutController` into `App\Http\Controllers\Api\V1\Account\AccountOrdersController`;
   - canonical account endpoints added:
     - `GET /api/v1/account/orders`,
     - `GET /api/v1/account/orders/{order}`,
     - `GET /api/v1/account/orders/summary`;
   - legacy aliases preserved:
     - `GET /api/v1/orders/me`,
     - `GET /api/v1/orders/me/summary`;
   - account read application layer moved into `App\Application\Account\Orders\*` with explicit query handlers, DTOs, and `AccountOrderReadRepository` contract;
   - account read persistence extracted to `App\Repositories\AccountOrderReadRepository`;
   - account list payload now uses summary-only canonical read-model with explicit detail endpoint;
   - frontend account orders migrated to summary list + lazy detail loading with stale-response suppression and shared route-query schema helper;
   - duplicated authenticated-user resolution extracted into shared controller concern;
   - deterministic backend/frontend coverage added for:
     - canonical summary list shape,
     - owner-scoped detail loading,
     - stale detail response suppression,
     - legacy alias backward compatibility.
9. Wave 16 completed (category selector decoupling and shared option query):
   - dedicated selector endpoint added:
     - `GET /api/v1/admin/categories/options`;
   - selector-specific backend read-model introduced via:
     - `AdminCategoryOptionListFilterDto`,
     - `AdminCategoryOptionResultDto`,
     - `AdminCategoryOptionsResultDto`,
     - `ListAdminCategoryOptionsQuery/Handler`,
     - `CategoryRepository::listOptionsForAdmin()`;
   - frontend category selector loading centralized into shared:
     - `resources/js/api/admin/categories.ts` (`listAdminCategoryOptions`);
     - `resources/js/composables/admin/categories/useAdminCategoryOptionsState.ts`;
   - admin category parent selector and admin product category selector now share the same options source and no longer depend on paginated management-list payloads or all-pages traversal;
   - deterministic backend/frontend coverage added for:
     - selector ordering,
     - `exclude_id` handling,
     - stale options response suppression,
     - shared reuse across category and product admin flows.
10. Wave 17 completed (maintenance cleanup resource strategy and scale safety):
   - cleanup plan and resource boundaries introduced:
     - `MaintenanceCleanupPlanFactory`,
     - `MaintenanceCleanupResource` contract,
     - `CheckoutIdempotencyCleanupResource`,
     - `WebhookReceiptCleanupResource`,
     - `ActiveCartCleanupResource`,
     - `InactiveCartCleanupResource`;
   - `MaintenanceCleanupExecutor` reduced to orchestration over typed cleanup plan/resources;
   - cleanup execution now uses deterministic batched deletion with config-driven `cleanup.batch_size`;
   - cleanup result reporting expanded with per-resource batch counts and aggregate totals;
   - additive index support added for cleanup predicates:
     - `carts(status, updated_at)`,
     - `webhook_receipts(created_at)`;
   - deterministic coverage added for:
     - cleanup plan order/cutoffs,
     - batched execution accounting,
     - cleanup schema index support,
     - command output/reporting contract.
11. Wave 18 completed (read repository boundary split and product write decomposition):
   - mixed read repositories split into bounded read paths:
     - `AdminOrderReadRepository`,
     - `AdminProductReadRepository`,
     - `CatalogProductReadRepository`;
   - legacy mixed-context repository classes removed:
     - `OrderRepository`,
     - `ProductRepository`;
   - account-order summary status interpretation extracted out of repository layer into:
     - `AccountOrderSummaryAggregateDto`,
     - `AccountOrderSummaryStatusGroupDto`,
     - `AccountOrderSummaryProjector`;
   - duplicated admin/account order search filter logic centralized into shared repository concern:
     - `App\Repositories\Concerns\AppliesOrderSearch`;
   - `AdminCatalogService` reduced to transaction orchestration over explicit collaborators:
     - `AdminProductVariantResolver`,
     - `AdminProductVariantSyncService`,
     - `AdminVariantInventorySyncService`,
     - `AdminVariantPriceSyncService`;
   - repository architecture guardrails added for:
     - bounded read-context file split,
     - no derived account-order status semantics inside repositories;
   - deterministic unit coverage added for:
     - account-order summary projection,
     - admin product variant resolution and SKU collision rules,
     - variant sync stale-deletion behavior,
     - inventory and price upsert collaborators.
12. Wave 19 completed (infrastructure provider hygiene and container guardrails):
   - `AppServiceProvider` reduced to boot-only application bootstrap responsibilities (policies + rate limiters);
   - container bindings split into dedicated provider modules:
     - `ApplicationBindingsServiceProvider`,
     - `AuthBindingsServiceProvider`,
     - `GatewayServiceProvider`,
     - `MaintenanceServiceProvider`,
     - `ObservabilityServiceProvider`;
   - gateway driver resolution moved into `GatewayServiceProvider` with existing `payment.driver` / `shipping.driver` semantics preserved;
   - observability alert-router binding moved into `ObservabilityServiceProvider` with existing channel and cooldown wiring preserved;
   - provider bootstrap registration normalized in `bootstrap/providers.php` so concern-specific providers load explicitly rather than accumulating in one global provider;
   - infrastructure architecture guardrail added:
     - `tests/Unit/Architecture/InfrastructureProviderBoundaryTest`,
     covering:
       - specialized provider registration,
       - `AppServiceProvider` bootstrap-only constraint,
       - ownership of `register()` logic by specialized providers.
13. Wave 20 completed (archive and documentation hygiene):
   - archived plan documents normalized with explicit non-authoritative banners that point back to `docs/ARCHITECTURE_REFACTOR_NEXT.md`:
     - `docs/ARCHITECTURE_REFACTOR_PLAN.md`,
     - `docs/DEEP_REFACTORING_PLAN.md`,
     - `docs/DTO_IMPLEMENTATION_PLAN.md`,
     - `docs/TEMPLATE_COMPONENTIZATION_PLAN.md`;
   - release and operational docs normalized toward canonical aliases:
     - `README.md`,
     - `docs/PHASE5_RELEASE_READINESS_CHECKLIST.md`,
     - `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md`;
   - residual docs drift fixed:
     - stale numbering in checkout incident flow,
     - duplicate raw quality/smoke command examples reduced in favor of canonical composer aliases,
     - release checklist now references `ops:clear`, `ops:routes-smoke`, and `ops:observability-report`;
   - documentation authority guardrails added:
     - `tests/Unit/Architecture/DocumentationAuthorityGuardrailTest`,
     covering:
       - explicit archival banners on historical plan files,
       - non-authoritative banner on `docs/REFACTORING_EXECUTION_PLAN.md`;
   - existing docs guardrails expanded so release/operational docs stay aligned with:
     - the active roadmap,
     - canonical release/ops aliases.
14. Wave 21 completed (transport validation consistency):
   - `CatalogController::index()` migrated from inline `$request->validate()` to dedicated `CatalogIndexRequest`;
   - catalog list transport parsing now lives behind:
     - `app/Http/Requests/Catalog/CatalogIndexRequest.php`,
     with typed `filter()` and `perPage()` accessors;
   - checkout `Idempotency-Key` enforcement extracted out of `CheckoutController::placeOrder()` into:
     - `app/Http/Middleware/EnsureIdempotencyKeyMiddleware.php`,
     wired via `bootstrap/app.php` alias and applied on `POST /api/v1/checkout/place-order`;
   - `PlaceOrderRequest` now exposes normalized header parsing through a typed accessor instead of controller-level inline header validation;
   - architecture guardrail added:
     - `tests/Unit/Architecture/ApiControllerValidationBoundaryTest`,
     forbidding inline `->validate()` calls inside API V1 controllers;
   - deterministic feature coverage added for:
     - invalid catalog filter rejection through FormRequest validation,
     - missing `Idempotency-Key` rejection before checkout controller execution.
15. Shared authenticated-user transport boundary hardened:
   - `app/Http/Controllers/Concerns/ResolvesAuthenticatedUser.php` now exposes:
     - `resolveAuthenticatedUser()` for guest-capable flows,
     - `requireAuthenticatedUser()` for auth-required controller actions with centralized `401` API response handling;
   - auth-required API V1 controllers migrated away from raw `$request->user()` and repeated inline unauthenticated responses:
     - `app/Http/Controllers/Api/V1/Auth/AuthController.php`,
     - `app/Http/Controllers/Api/V1/Auth/VerificationController.php`,
     - `app/Http/Controllers/Api/V1/Account/AccountOrdersController.php`;
   - architecture guardrail added:
     - `tests/Unit/Architecture/ApiControllerAuthenticatedUserBoundaryTest`,
     forbidding inline authenticated-user resolution and repeated raw `Authentication is required.` transport responses inside API V1 controllers;
   - deterministic regression coverage added for:
     - account orders auth requirement,
     - auth logout token revocation.
16. Wave 22 completed (frontend price formatting and utility consolidation):
   - canonical frontend money formatting centralized in:
     - `resources/js/utils/format.ts` (`formatPrice(value, currency?, locale?)`);
   - legacy `formatMoney(...)` now delegates to the canonical formatter, keeping existing order/account/admin callers backward-compatible while removing duplicate formatter implementations;
   - raw `Number(value).toFixed(2)` duplication removed from:
     - `resources/js/components/cart/CartSummaryHeader.vue`,
     - `resources/js/components/cart/CartItemsTable.vue`,
     - `resources/js/pages/CatalogPage.vue`,
     - `resources/js/pages/ProductPage.vue`;
   - catalog/product presentational contracts aligned with canonical currency-aware formatting so price labels no longer duplicate trailing currency codes in the template layer;
   - catalog composables gained explicit test seams via injected route/router adapters:
     - `useCatalogProducts({ route, router })`,
     - `useCatalogProduct({ route })`;
   - deterministic composable coverage added for:
     - `useCatalogProducts`,
     - `useCatalogProduct`,
     - additional error-path coverage in `useCheckoutPageViewModel`,
     - additional error-path coverage in `useAuthPageViewModel`;
   - `CartItemsTable` / `OrderItemsTable` structural extraction was assessed and intentionally deferred because the shared surface is currently smaller than their divergent interaction responsibilities (editable cart controls vs read-only order presentation).
17. Wave 23 completed (static analysis hardening):
   - PHPStan level 6 baseline eliminated completely:
     - `phpstan.neon` no longer includes `phpstan-baseline.neon`;
     - `phpstan-baseline.neon` removed from the repository.
   - stale suppression debt removed by fixing source typing instead of carrying ignores:
     - gateway webhook payload contracts documented as typed payload arrays;
     - model relation, factory, and key-type generics tightened across Eloquent models;
     - repository and service branches simplified after stronger typing made dead `instanceof` guards obsolete;
     - `Order::hasCapturedPayment()` introduced so payment-state checks stop relying on loose string access.
   - dead transport artifacts removed:
     - legacy `app/Http/Resources/*Resource.php` files deleted;
     - low-value `tests/Unit/ExampleTest.php` removed.
   - new architecture guardrail added:
     - `tests/Unit/Architecture/LegacyTransportResourceArtifactGuardrailTest`,
     preventing `app/Http/Resources` drift from reappearing after the DTO transport migration.
   - PHPStan level 7 feasibility explicitly assessed:
     - current blocker set measured at `55` errors;
     - dominant cluster is feature-command test typing (`Observability*`, smoke-command, webhook, and cleanup command tests);
     - secondary cluster is a small number of object-shape/non-object access spots in requests, maintenance batching, and a few architecture tests.
18. Wave 24 completed (architecture guardrail expansion):
   - repository boundary protection expanded with:
     - `tests/Unit/Architecture/RepositoryBusinessDecisionBoundaryTest`,
     forbidding read repositories from depending on authorization, transition-policy, or business-outcome boundaries and from returning boolean business decisions.
   - queued side-effect safety guardrail added:
     - `tests/Unit/Architecture/QueuedJobSafetyGuardrailTest`,
     covering:
       - `afterCommit()` usage on committed side-effect dispatch paths;
       - scalar-or-array queue payload discipline for queued jobs;
       - preservation of prevalidated webhook event identity across the queue boundary.
   - policy completeness matrix guardrail added:
     - `tests/Unit/Architecture/PolicyCompletenessMatrixGuardrailTest`,
     covering:
       - Gate registration for all route-bound policy models;
       - presence of the actions currently exercised by routes and FormRequests;
       - explicit `bool` return types on those policy methods.
   - existing repository and policy tests remain complementary:
     - `RepositoryReadBoundaryTest`,
     - `RepositoryStatusInterpretationGuardrailTest`,
     - `AdminPolicyMatrixTest`.
19. Post-wave type-safety hardening completed:
   - test console typing hardened through a shared typed command helper in `tests/TestCase.php`, removing `PendingCommand|int` ambiguity from command feature tests;
   - feature and unit tests tightened for:
     - webhook order/shipment retrieval return shapes,
     - anonymous notification routing typing,
     - scheduler command list typing,
     - architecture allowlist class-string extraction,
     - maintenance schema row access.
   - request and service narrowings completed for remaining level 7 object-shape spots:
     - typed route-model helpers in admin update FormRequests;
     - normalized persisted webhook payload merge inputs in payment/shipping transition appliers;
     - list normalization in batched maintenance cleanup resources.
   - static-analysis config upgraded:
     - `phpstan.neon` raised from level `6` to level `7`.
   - verification result:
     - PHPStan level 7 now reports `0` errors across `app/`, `routes/`, and `tests/`.
20. Post-wave strict type hardening completed:
   - remaining level 8 nullable/return-type gaps closed across application and service paths:
     - `AuthUserDtoMapper` string normalization for nullable profile fields;
     - `CheckoutPaymentResultDto` transaction-id normalization;
     - non-null refresh/load return paths in admin catalog/category/order services and cart mutation service;
     - explicit smoke-scenario guards for payment transaction and shipment tracking identifiers;
     - strict unit-test narrowing in admin variant price sync coverage.
   - static-analysis config upgraded again:
     - `phpstan.neon` raised from level `7` to level `8`.
   - verification result:
     - PHPStan level 8 now reports `0` errors across `app/`, `routes/`, and `tests/`.
21. Post-wave strict type hardening continued:
    - remaining level 9 mixed-cast, payload-shape, config, repository eager-load, smoke, and test helper gaps were closed across application, support, and test layers;
     - shared strict-typing helpers now centralize scalar and payload normalization in `App\Support\Data\TypedValue`;
     - static-analysis config upgraded again:
       - `phpstan.neon` raised from level `8` to level `9`.
     - verification result:
       - PHPStan level 9 now reports `0` errors across `app/`, `routes/`, and `tests/`.
22. Post-wave strict type hardening completed:
     - remaining level 10 `mixed`/generic variance gaps were closed across webhook transport, middleware return boundaries, maintenance resource resolution, eager-load closures, smoke helpers, and schema/test helpers;
     - static-analysis config upgraded again:
       - `phpstan.neon` raised from level `9` to level `10`;
     - verification result:
       - PHPStan level 10 now reports `0` errors across `app/`, `routes/`, and `tests/`.
23. Promoted backlog safety slice completed (`Backlog A` from `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md`):
    - cart mutation concurrency hardening completed:
      - `CartMutationService::upsertItem()` and `removeItem()` now run inside explicit transactions with locked cart state and locked item mutation paths;
      - stale in-memory cart state no longer drives `CartItem` upsert decisions;
      - regression coverage added in `tests/Feature/CartMutationSafetyTest.php`.
    - webhook transition and dedupe safety tightened:
      - `PaymentWebhookTransitionApplier` and `ShippingWebhookTransitionApplier` now lock the related `Order` row before applying order-state updates;
      - `WebhookProcessingPipeline` moved from `firstOrCreate()` race window to unique-key `insertOrIgnore` plus locked receipt fetch inside the transaction;
      - duplicate-event and transition regressions remain covered by existing payment/shipping webhook suites.
    - checkout pay transport boundary hardened:
      - new `InitiatePaymentRequest` introduced for `/api/v1/checkout/orders/{order}/pay`;
      - canonical `idempotency.key` middleware now also protects the pay route;
      - fallback `pay-{order}` idempotency generation removed from `CheckoutController::pay()`;
      - regression coverage added for missing `Idempotency-Key` header on pay initiation.
    - SPA auth/forbidden response handling centralized:
     - `resources/js/api/client.ts` now installs explicit response handling for `401` and `403`;
     - unauthorized handling is single-flight and clears local auth state before redirecting to `/auth`;
     - forbidden responses now surface through shared shell notice state via `resources/js/stores/app-shell.ts`;
     - regression coverage added in `resources/js/tests/api/client-response-handling.spec.ts`.
24. Promoted backend boundary-hygiene slice completed (`Backlog B` from `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md`):
    - DomainException transport handling centralized:
      - `bootstrap/app.php` now maps `\DomainException` to `422` for API requests;
      - inline `catch (DomainException)` handling removed from API V1 controllers:
        - cart, checkout, auth login, admin order status update, payment webhook, shipping webhook.
      - architecture guardrail added:
        - `tests/Unit/Architecture/ApiControllerDomainExceptionBoundaryTest.php`.
    - policy and authorization hygiene completed:
      - `CouponPolicy` expanded to full action matrix (`viewAny`, `view`, `create`, `update`, `delete`);
      - policy guardrail matrix updated to enforce the expanded coupon action set;
      - admin policy matrix tests updated for coupon role coverage;
      - `CacheController::refreshCatalog()` now performs explicit `authorize('viewAny', Product::class)` in addition to route middleware.
    - transition and discount boundary cleanup completed:
      - `PaymentStatusTransitionPolicy` and `ShipmentStatusTransitionPolicy` promoted to `final readonly` stateless policies;
      - `CheckoutDiscountResolver` switched from `getRawOriginal(...)` datetime checks to casted datetime attributes (`expires_at`, `starts_at`, `ends_at`) with explicit typed local variables.
25. Promoted frontend consistency slice completed (`Backlog C` from `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md`):
    - API assertion primitives centralized:
      - shared `resources/js/contracts/api/v1/assertions/primitives.ts` now provides `isRecord` and typed field parsers;
      - duplicated local primitive helpers removed from all V1 assertion modules (`auth`, `catalog`, `cart`, `checkout`, `admin-*`, `account-orders`).
    - frontend duplication cleanup completed:
      - `RoleName` extracted to `resources/js/types/auth.ts` and reused by router/store boundaries;
      - catalog query parser now imports shared `toSingleQueryValue` from `resources/js/queries/route-query.ts`;
      - repeated admin/account order address normalization extracted to `resources/js/mappers/common.ts` and reused by both order mapper modules.
    - checkout result-state hardening completed:
      - `resources/js/composables/checkout/useCheckoutPageViewModel.ts` now uses explicit `CheckoutResultState` (`idle | success | error`);
      - `isResultSuccess` now derives from state enum instead of string-prefix heuristics on user-visible message text.
    - store storage coupling reduced:
      - shared storage adapter boundary added in `resources/js/utils/storage.ts` (browser/noop/in-memory implementations);
      - `resources/js/stores/auth.ts` and `resources/js/stores/cart.ts` migrated from direct `localStorage` usage to injected adapter boundary;
      - deterministic in-memory storage coverage added/updated in:
        - `resources/js/tests/auth-store.spec.ts`;
        - `resources/js/tests/cart-store.spec.ts`;
        - `resources/js/tests/composables/use-checkout-page-view-model.spec.ts`.
26. Promoted deep-domain checkout foundation slice completed (`Backlog D` partial from `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md`, items `9` and `10`):
    - checkout command orchestration moved out of application handler:
      - new `App\Services\Checkout\CheckoutPlaceOrderOrchestrator` now owns guest-cart merge, checkout cart resolution, order placement, and payment initiation flow;
      - `PlaceCheckoutOrderHandler` reduced to transport/application shell that delegates to orchestrator.
    - shipping calculation extracted behind explicit contract:
      - `CheckoutShippingCostResolver` interface introduced;
      - `FreeCheckoutShippingCostResolver` added as current default implementation;
      - `CheckoutService` now resolves `shippingTotal` through the resolver contract instead of hardcoded literal.
    - container binding and regression coverage added:
      - `ApplicationBindingsServiceProvider` binds `CheckoutShippingCostResolver` to `FreeCheckoutShippingCostResolver`;
      - `tests/Unit/CheckoutShippingCostResolverBindingTest.php` verifies binding and current default result;
      - checkout feature suites remain green and now execute via orchestrator path.
27. Promoted deep-domain payment-status domain extraction completed (`Backlog D` incremental continuation, item `8` foundation):
    - payment-status business logic moved out of `Order` model:
      - extracted `App\Domain\Order\OrderPaymentStatusResolver`;
      - removed `hasCapturedPayment()` and `normalizedPaymentStatus()` helpers from `App\Models\Order`.
    - side-effect consumers switched to domain service:
      - `QueueOrderSideEffects` now uses `OrderPaymentStatusResolver` before dispatching `DispatchShipmentJob`;
      - `DispatchShipmentJob` now resolves the same domain service in `handle()` and no longer depends on model helper methods.
    - guardrails and unit coverage added:
      - `tests/Unit/OrderPaymentStatusResolverTest.php` verifies enum/string/null normalization and captured-payment detection;
      - `tests/Unit/Architecture/OrderModelBusinessLogicBoundaryTest.php` prevents payment-status business helpers from returning to `Order` model.
28. Promoted deep-domain money value-object foundation completed (`Backlog D` incremental continuation, item `7` foundation):
    - domain value object introduced:
      - `App\Domain\ValueObjects\Money` (`cents + currency`) with explicit arithmetic and rounding semantics (`add`, `subtract`, `min`, `percentage`).
    - checkout core flow migrated to money-boundary internals:
      - `CheckoutCartPreparer` now accumulates subtotal as `Money`;
      - `CheckoutDiscountResolver` now computes discount as `Money`;
      - `CheckoutShippingCostResolver` contract now returns `Money`;
      - `CheckoutOrderWriter` now performs total arithmetic via `Money` and converts to scalar only at persistence boundary.
    - dto boundary updates for internal consistency:
      - `CheckoutCartLineItemDto`, `CheckoutCartPreparationDto`, `CheckoutOrderWriteInputDto`, and `CheckoutDiscountContextDto` now carry `Money` for internal calculations while preserving existing API payload shape.
    - unit coverage added/updated:
      - `tests/Unit/Domain/ValueObjects/MoneyTest.php`;
      - `tests/Unit/CheckoutShippingCostResolverBindingTest.php`;
      - `tests/Unit/CheckoutOrderFinalizerTest.php`.
29. Promoted platform-enablement factory foundation completed (`Backlog E` incremental start, item `20` foundation):
    - new test factories introduced for catalog/cart/order/promotion domains:
      - `CategoryFactory`, `ProductFactory`, `ProductVariantFactory`, `InventoryFactory`, `PriceFactory`,
      - `CartFactory`, `CartItemFactory`,
      - `OrderFactory`, `OrderItemFactory`,
      - `PromotionFactory`, `CouponFactory`.
    - seeder coupling reduction started:
      - `CartMutationSafetyTest` no longer depends on `CatalogSeeder`; it now assembles required catalog/inventory state through factories.
    - deterministic factory coverage added:
      - `tests/Unit/FactoryCoverageTest.php` verifies catalog aggregate, cart/order item relations, and promotion/coupon linkage can be created without seeders.
30. Promoted platform-enablement factory adoption progressed (`Backlog E` incremental continuation, item `20`):
    - checkout feature tests decoupled from catalog seeding:
      - `GuestCheckoutTest`,
      - `CouponCheckoutTest`,
      - `CartCheckoutTest`,
      - `CheckoutAuthenticatedTokenTest`
      now build active catalog state via factories instead of `CatalogSeeder`.
    - shared test fixture helper introduced:
      - `tests/Concerns/CreatesCatalogVariant.php` provides explicit `Product + ProductVariant + Inventory` setup for checkout-facing tests.
    - role-bound auth contracts remain explicit:
      - `RoleSeeder` kept where role assignment is part of test intent; only catalog bootstrap moved to factories.
31. Promoted platform-enablement factory adoption progressed (`Backlog E` incremental continuation, item `20` webhook coverage):
    - webhook feature tests decoupled from `CatalogSeeder` bootstrap:
      - `PaymentWebhookTest`,
      - `ShippingWebhookTest`
      now build checkout-capable catalog state via shared factory fixture.
    - shared fixture reuse expanded:
      - `tests/Concerns/CreatesCatalogVariant.php` is now used across checkout and webhook feature suites, reducing duplicated catalog setup patterns.
    - webhook safety semantics preserved:
      - replay idempotency, hash mismatch protection, regression-guarded status transitions, and pay-endpoint idempotency-header assertions remain green after fixture migration.
32. Promoted platform-enablement factory adoption progressed (`Backlog E` incremental continuation, item `20` catalog/hardening coverage):
    - catalog feature tests decoupled from `CatalogSeeder`:
      - `CatalogTest` now initializes active catalog state through factory fixtures.
    - phase-one hardening public/cart/checkout path decoupled from catalog seeding:
      - `PhaseOneHardeningTest` storefront/cart/checkout scenarios now use factory-based variant/product setup while manager-role admin transition cases retain explicit `RoleSeeder`.
    - shared fixture capabilities expanded:
      - `CreatesCatalogVariant` now provides `createActiveProductWithVariants(...)` in addition to single-variant inventory setup.
33. Promoted platform-enablement factory adoption progressed (`Backlog E` incremental continuation, item `20` admin-promotion/performance coverage):
    - admin promotion checkout-flow feature tests decoupled from `CatalogSeeder`:
      - `AdminPromotionCouponFlowTest` now uses shared factory fixture setup for checkout-capable variant creation.
    - performance smoke feature tests decoupled from `CatalogSeeder`:
      - `PerformanceSmokeTest` now creates deterministic catalog fixtures through `CreatesCatalogVariant` instead of seeder bootstrap.
    - no `CatalogSeeder` coupling remains in `tests/Feature/*`:
      - feature-suite catalog bootstrap now consistently uses explicit factory fixtures, while `RoleSeeder` remains only for role-contract intent.
34. Promoted domain-event expansion foundation completed (`Backlog E` incremental continuation, item `21` foundation):
    - status transition domain events introduced with after-commit semantics:
      - `OrderStatusChanged`,
      - `PaymentStatusChanged`,
      - `ShipmentStatusChanged`.
    - post-payment webhook side effects moved behind event listener boundary:
      - `PaymentWebhookTransitionApplier` now emits `PaymentStatusChanged` instead of dispatching jobs directly;
      - `QueuePaymentStatusSideEffects` now owns `SendOrderConfirmationJob` / `DispatchShipmentJob` dispatch with explicit first-capture guard.
    - transition audit subscribers added:
      - `LogOrderStatusTransition`,
      - `LogShipmentStatusTransition`.
    - order-status change emission adopted in mutating flows:
      - payment webhook transition path;
      - shipping webhook transition path;
      - admin order status update path.
    - event wiring + regression coverage added:
      - `EventServiceProvider` now maps status events to dedicated listeners;
      - unit/feature coverage extended in:
        - `PaymentWebhookTransitionApplierTest`,
        - `ShippingWebhookTransitionApplierTest`,
        - `AdminOrderServiceStatusEventTest`,
        - `QueuePaymentStatusSideEffectsTest`.
35. Promoted domain-event expansion progressed (`Backlog E` incremental continuation, item `21` metrics-subscriber slice):
    - observability metric subscribers added for status-transition events:
      - `RecordOrderStatusTransitionMetric`,
      - `RecordPaymentStatusTransitionMetric`,
      - `RecordShipmentStatusTransitionMetric`.
    - event payloads enriched with transition source where needed:
      - `PaymentStatusChanged` and `ShipmentStatusChanged` now carry `source`;
      - webhook transition appliers emit typed sources (`payment_webhook`, `shipping_webhook`).
    - observability module extended with status-transition metric pipeline:
      - `ObservabilityService::statusTransition(...)` added;
      - `ObservabilityMetricStore` now stores and aggregates `status_transition` counters by `domain + from + to + source`.
    - event/listener wiring expanded:
      - `EventServiceProvider` now maps status-transition metric listeners alongside existing audit and side-effect listeners.
    - deterministic coverage added/updated:
      - `StatusTransitionMetricListenersTest`,
      - `ObservabilityMetricStoreTest`,
      - `ObservabilityServiceTest`,
      - plus webhook transition regression suites to ensure event-contract compatibility.
36. Promoted domain-event expansion progressed (`Backlog E` incremental continuation, item `21` notification-side-effects slice):
    - dedicated order-status side-effect listener added:
      - `QueueOrderStatusSideEffects` now handles `OrderStatusChanged` and dispatches queued notification flow for customer-facing milestones.
    - new queued notification job introduced:
      - `SendOrderStatusChangedNotificationJob` dispatches `OrderStatusChangedNotification` via on-demand mail routing (`order.email`) with scalar payload-only queue boundary.
    - notification scope is explicit and reversible:
      - notification dispatch currently targets `shipped`, `completed`, `cancelled`, and `refunded` statuses only.
    - event wiring + queue-safety guardrails extended:
      - `EventServiceProvider` now wires `QueueOrderStatusSideEffects` for `OrderStatusChanged`;
      - `QueuedJobSafetyGuardrailTest` now enforces `afterCommit` dispatch path and scalar payload contract for `SendOrderStatusChangedNotificationJob`.
    - deterministic coverage added:
      - `QueueOrderStatusSideEffectsTest`,
      - `SendOrderStatusChangedNotificationJobTest`,
      - plus existing admin/webhook order-status transition suites remain green.
37. Promoted domain-event expansion progressed (`Backlog E` incremental continuation, item `21` config-contract hardening slice):
    - order-status notification policy moved to explicit config contract:
      - added `config/orders.php` with `orders.status_notifications.notifiable_statuses`.
    - listener notification scope now resolves from config with enum-safe validation:
      - `QueueOrderStatusSideEffects` now parses configured statuses through `OrderStatus::tryFrom(...)` and fails fast for invalid entries.
    - config contract coverage extended:
      - `OperationalDocsConfigGuardrailTest` now validates `orders.status_notifications.notifiable_statuses` contains only supported `OrderStatus` values.
    - deterministic listener coverage extended:
      - `QueueOrderStatusSideEffectsTest` now verifies config-driven dispatch/skip behavior and invalid-config failure semantics.
38. Promoted platform-enablement infrastructure progressed (`Backlog E` incremental continuation, item `22` docker-compose foundation slice):
    - root local compose entrypoint added:
      - `docker-compose.yml` now provides `app + nginx + mysql:8.4 + redis:7` stack from repository root.
    - local-stack contract guardrail added:
      - `DockerComposeContractGuardrailTest` now validates required service/image/env contract for both:
        - root `docker-compose.yml`,
        - compatibility file `docker/compose.yml`.
    - local runbook updated:
      - `README.md` now includes Docker Compose quick-start commands for local parity with MySQL/Redis runtime.
39. Promoted domain-event expansion progressed (`Backlog E` incremental continuation, item `21` typed-source boundary slice):
    - status-transition event source moved from raw strings to typed enum contract:
      - added `app/Domain/Order/StatusTransitionSource.php`.
    - transition events now enforce enum source type:
      - `OrderStatusChanged`,
      - `PaymentStatusChanged`,
      - `ShipmentStatusChanged`.
    - all emitters/listeners migrated to enum-safe source handling:
      - payment/shipping webhook transition appliers and admin order status service emit enum cases;
      - logging/metric/notification listeners map source through explicit scalar boundary (`->value`) where needed.
    - architecture guardrail added:
      - `StatusTransitionSourceBoundaryTest` enforces typed event source constructor contract and forbids raw source literals in transition emitters.
40. Promoted platform-enablement infrastructure progressed (`Backlog E` incremental continuation, item `22` docker-ops alias slice):
    - canonical local docker operations added to composer aliases:
      - `ops:docker-up`,
      - `ops:docker-down`,
      - `ops:docker-bootstrap`.
    - local docker onboarding in README migrated to canonical aliases:
      - quick-start now uses `composer run ops:docker-bootstrap`;
      - stop flow now uses `composer run ops:docker-down`.
    - guardrails extended for alias/docs parity:
      - `ReleaseCommandScriptGuardrailTest` now enforces docker alias script contracts;
      - `ReleaseDocsWorkflowGuardrailTest` now enforces docker alias references in README.
41. Promoted platform-enablement infrastructure progressed (`Backlog E` incremental continuation, item `22` release-doc/runbook parity slice):
    - release checklist updated with canonical docker alias block:
      - `docs/PHASE5_RELEASE_READINESS_CHECKLIST.md` now references:
        - `composer run ops:docker-up`,
        - `composer run ops:docker-down`,
        - `composer run ops:docker-bootstrap`.
    - ops runbook updated with local parity bootstrap/teardown flow:
      - `docs/OPERATIONS_RUNBOOK_CHECKOUT_WEBHOOKS.md` now includes local investigation baseline using docker aliases.
    - release docs guardrail expanded:
      - `ReleaseDocsWorkflowGuardrailTest` now enforces docker alias references in:
        - README,
        - release checklist,
        - ops runbook.
42. Promoted `Deep Architecture Audit & Refactoring Plan v2` safety-first slice started via `Backlog F` (`P0`, items `1-3`):
    - admin order direct status transition guard introduced:
      - `OrderStatusTransitionPolicy` promoted to `final readonly` and expanded with explicit `canTransitionDirectly(from, to)` matrix;
      - `AdminOrderService` now rejects invalid direct status updates (`DomainException: "Order status transition is not allowed."`) when `status` is explicitly provided.
    - cart remove-item transport validation hardened:
      - new `RemoveCartItemRequest` validates route `variantId` via `integer + exists:product_variants,id` and normalizes `guest_token` from query/header;
      - cart delete route now includes numeric constraint via `->whereNumber('variantId')`.
    - explicit cart authorization policy boundary introduced:
      - new `CartPolicy` mapped in `AppServiceProvider`;
      - `CartController` now performs explicit `authorize()` calls:
        - `viewAny` for cart read;
        - `modify` for cart mutations (authenticated user or guest token scope).
    - policy guardrails and regressions expanded:
      - `PolicyCompletenessMatrixGuardrailTest` now includes `Cart -> CartPolicy` action contract;
      - new/updated regressions:
        - `CartMutationSafetyTest` (unknown variant remove validation + guest mutation token requirement),
        - `OrderStatusTransitionPolicyTest` (direct-transition matrix),
        - `AdminOrderServiceStatusEventTest`,
        - `PhaseOneHardeningTest` (invalid direct admin transition rejection).
43. Promoted `Deep Architecture Audit & Refactoring Plan v2` domain-model expansion started via `Backlog G` (`P1`, item `4` cart money expansion):
    - monetary write-path in cart mutation migrated to `Money` value object:
      - `CartMutationService` now computes `unit_price` and `line_total` using `Money::fromDecimal(...)->multiply(quantity)` instead of `bcmul` float/string arithmetic;
      - persistence boundary remains scalar (`toFloat`) to preserve existing DB/API contracts.
    - cart read-path summary aggregation migrated to `Money`:
      - `CartResultMapper` now accumulates subtotal/total via `Money` and only maps to float at response boundary;
      - per-item unit and line totals are normalized through `Money` prior to DTO mapping.
    - domain value-object capability expanded:
      - `Money` now includes `multiply(int $factor)` for deterministic cent-safe quantity scaling.
    - deterministic regression coverage added/updated:
      - `MoneyTest` now verifies cent-safe multiplication semantics;
      - `CartResultMapperTest` now verifies decimal subtotal aggregation precision (`0.1 + 0.2 = 0.3`) through mapper boundary;
      - `CartMutationSafetyTest` now asserts line-total persistence via `Money` calculation contract.
44. Promoted `Deep Architecture Audit & Refactoring Plan v2` domain-model expansion progressed via `Backlog G` (`P1`, item `5` order/payment money-boundary slice):
    - order detail DTO mapping normalized through `Money` while preserving response contract:
      - `CheckoutOrderResultDto`, `AdminOrderDetailResultDto`, `AccountOrderDetailResultDto` now map monetary fields through `Money` in `fromOrder(...)` and still expose `float` values on JSON boundary.
    - payment creation boundary migrated to typed money contract:
      - `PaymentGatewayInterface::createPayment(...)` now receives explicit `Money $amount`;
      - `PaymentService` now resolves order total as `Money` before gateway invocation and before payment persistence write;
      - fake gateway payload now includes normalized amount/currency from typed `Money` boundary.
    - deterministic regression coverage added/updated:
      - `OrderMoneyDtoMappingTest` validates cross-DTO money mapping consistency on float boundary;
      - `GatewayDriverBindingTest` updated to reflect the typed gateway contract signature.
45. Promoted `Deep Architecture Audit & Refactoring Plan v2` domain-model expansion progressed via `Backlog G` (`P1`, item `6` service contracts slice):
    - explicit service contracts introduced for core checkout/cart boundaries:
      - `CheckoutServiceInterface`,
      - `CartServiceInterface`,
      - `CartMutationServiceInterface`.
    - default implementations now implement explicit contracts:
      - `CheckoutService`,
      - `CartService`,
      - `CartMutationService`.
    - container wiring aligned with contracts:
      - `ApplicationBindingsServiceProvider` now binds service contracts to default implementations.
    - consumers migrated from concrete services to contracts:
      - cart command/query handlers,
      - auth login handler guest-cart merge path,
      - checkout place-order orchestrator,
      - performance/webhook smoke scenario setup and execution paths.
    - deterministic coverage added/updated:
      - new `ApplicationServiceBindingTest` enforces contract-to-implementation container resolution;
      - `CartMutationSafetyTest` now resolves cart mutation service through interface contract.
46. Promoted `Deep Architecture Audit & Refactoring Plan v2` domain-model expansion progressed via `Backlog G` (`P1`, item `7` repository contracts slice):
    - explicit repository contracts introduced for remaining read boundaries:
      - `AdminOrderReadRepository` contract,
      - `AdminProductReadRepository` contract,
      - `AdminPromotionReadRepository` contract,
      - `AdminCategoryReadRepository` contract,
      - `CatalogProductReadRepository` contract.
    - default read repositories now implement their explicit contracts:
      - `App\Repositories\AdminOrderReadRepository`,
      - `App\Repositories\AdminProductReadRepository`,
      - `App\Repositories\PromotionRepository`,
      - `App\Repositories\CategoryRepository`,
      - `App\Repositories\CatalogProductReadRepository`.
    - application container wiring aligned with repository contracts:
      - `ApplicationBindingsServiceProvider` now binds each repository contract to its default infrastructure implementation.
    - application/smoke consumers migrated from concrete repositories to contracts:
      - admin orders/products/promotions/categories query handlers,
      - `CatalogService`,
      - admin performance smoke scenarios.
    - deterministic coverage added:
      - new `ApplicationRepositoryBindingTest` enforces contract-to-implementation container resolution across the five repository contracts.
47. Promoted `Deep Architecture Audit & Refactoring Plan v2` domain-model expansion progressed via `Backlog G` (`P1`, item `8` observability metric race-safety slice):
    - counter increment path in `ObservabilityMetricStore` made race-safe for concurrent updates:
      - removed read-modify-write sequence (`add -> increment -> get+put`) that could clobber parallel increments;
      - `incrementCounter(...)` now uses atomic cache operations:
        - `Cache::add(key, value, ttl)` for first write,
        - `Cache::increment(key, value)` for existing keys.
    - compatibility fallback retained for non-increment stores:
      - when `Cache::increment(...)` returns `false`, store falls back to additive `put` path with explicit integer normalization.
    - regression verification kept on observability behavior:
      - metric aggregation tests remain green for API/catalog/webhook/status-transition windows and report command paths.
48. Promoted `Deep Architecture Audit & Refactoring Plan v2` domain deepening started via `Backlog H` (`P1`, item `9` order state-machine consolidation):
    - canonical order state-machine API added:
      - `OrderStatusTransitionPolicy` now exposes `canTransition(from, to)` as primary full matrix check.
    - backward compatibility preserved:
      - `canTransitionDirectly(from, to)` retained as alias and delegates to `canTransition(...)` for existing call-sites.
    - matrix normalized for explicit self-transitions:
      - all `OrderStatus` states now include self-transition in allowed matrix (`pending -> pending`, ..., `refunded -> refunded`) aligned with payment/shipment policies.
    - order-state semantics clarified:
      - `processing` and `shipped` remain explicit manual/admin order states;
      - webhook-driven resolution remains intentionally collapsed to deterministic customer-facing outcomes.
    - adoption + deterministic coverage:
      - `AdminOrderService` now uses canonical `canTransition(...)`;
      - `OrderStatusTransitionPolicyTest` now validates full matrix across all `OrderStatus::cases()` and asserts alias parity.
49. Promoted `Deep Architecture Audit & Refactoring Plan v2` domain deepening progressed via `Backlog H` (`P1`, item `10` webhook failure observability slice):
    - webhook pipeline failure logging added without changing processing semantics:
      - `WebhookProcessingPipeline` now logs `webhook.processing_failed` before rethrowing any throwable.
    - failure context normalized for deterministic triage:
      - log context now includes `provider`, `correlation_id`, `event_id`, `event_type`, `receipt_id`, `payload_hash`, `outcome`, `source`, `exception_class`, `exception_message`.
    - coverage added for logging + rethrow behavior:
      - `WebhookProcessingPipelineTest` verifies structured error logging and original exception propagation.
50. Promoted `Deep Architecture Audit & Refactoring Plan v2` domain deepening progressed via `Backlog H` (`P1`, item `11` domain exception hierarchy slice):
    - typed domain exception taxonomy introduced:
      - `CartException`,
      - `CheckoutException`,
      - `OrderTransitionException`.
    - cart/checkout/order-transition services migrated from raw `DomainException` to specialized exception types:
      - cart mutation/resolution flows now throw `CartException`;
      - checkout identity/cart/inventory/discount/idempotency/finalization flows now throw `CheckoutException`;
      - admin order status guards now throw `OrderTransitionException`.
    - deterministic unit coverage aligned with exception types:
      - `CheckoutRequestIdentityResolverTest` asserts `CheckoutException`;
      - `AdminOrderServiceStatusEventTest` asserts `OrderTransitionException`;
      - new `CartResolverTest` asserts guest-token guard throws `CartException`.
51. Promoted AI execution-governance architecture slice:
    - added canonical architecture contract document:
      - `docs/ARCHITECTURE.md` now defines layer model, dependency direction, and reliability contracts.
    - added AI-first repository navigation map:
      - `docs/AI_REPO_MAP.md` now defines bounded-context entrypoints and flow-first navigation for implementation tasks.
    - agent rules synchronized with architecture docs:
      - `.cursorrules` and `AGENTS.md` now explicitly require pre-implementation navigation via `AI_REPO_MAP` and boundary enforcement from `ARCHITECTURE`.
    - architecture guardrails expanded:
      - `LayerDependencyDirectionGuardrailTest` enforces application/service/repository dependency direction;
      - `AiRepoMapGovernanceGuardrailTest` enforces architecture/repo-map docs presence and rule references.
52. Promoted repo-map canonicalization slice (`REPO_MAP` + `DOMAIN_MAP`):
    - introduced canonical repository map docs for AI execution:
      - `docs/REPO_MAP.md` as repository-wide navigation and entrypoint map,
      - `docs/DOMAIN_MAP.md` as bounded-context dependency and ownership map.
    - retained backward compatibility for existing prompts:
      - `docs/AI_REPO_MAP.md` converted to compatibility alias that points to canonical maps.
    - synchronized architecture authority and agent policies:
      - `docs/ARCHITECTURE.md` now references repo/domain maps in authority section;
      - `.cursorrules` and `AGENTS.md` now require navigation via `REPO_MAP` + `DOMAIN_MAP`.
    - governance guardrail expanded to enforce:
      - canonical map docs existence and core sections,
      - alias continuity,
      - agent policy references to canonical map docs.
53. Promoted modular-monolith skeleton target slice:
    - domain-module skeleton introduced under `app/Domains/*`:
      - `Catalog`,
      - `Cart`,
      - `Checkout`,
      - `Orders`,
      - `Users`,
      - `Payments`,
      - `Webhooks`.
    - each domain module now has local README contract for incremental convergence toward:
      - `Controllers`,
      - `Services`,
      - `Repositories`,
      - `Models`.
    - architecture docs aligned with target physical layout:
      - `docs/ARCHITECTURE.md` now includes `Modular Monolith Target Layout` section;
      - `docs/REPO_MAP.md` now includes target layout section;
      - `docs/DOMAIN_MAP.md` now references domain-module path mapping.
    - policy docs aligned with modular convergence:
      - `.cursorrules` and `AGENTS.md` now explicitly prefer `app/Domains/*` for new domain-centric slices when compatibility permits.
    - architecture guardrails expanded:
      - `ModularMonolithSkeletonGuardrailTest` enforces domain skeleton presence and target-layout sections in architecture docs.

## Locked Constraints

1. Keep `/api/v1/*` envelope backward-compatible (`data/meta/error`).
2. Internal contracts may evolve to typed DTO/value objects.
3. Controller layer remains transport-only.
4. One logical block = one coherent commit-sized change.
5. No silent architecture tradeoffs; all exceptions must be explicit and reversible.
6. For Wave 4+ blocks, depth is mandatory:
   - extract logic into explicit boundary class/service/policy,
   - add deterministic tests for transition/rule matrix,
   - update execution log with checks run.

## Program Posture

1. This roadmap is a strengthening program, not a rescue rewrite.
2. Completed waves are presumed correct unless concrete regression evidence appears.
3. New waves must preserve current strong patterns and extend them into lagging layers.
4. Simplification that removes explicit boundaries, DTOs, handlers, orchestration shells, or guardrails is out of scope.

## Non-Goals

1. Do not reopen completed CQRS, controller-purity, webhook, or smoke-command refactors without explicit defect evidence.
2. Do not replace typed DTO boundaries with ad-hoc arrays, resources, or ORM leakage.
3. Do not merge account, admin, and catalog contexts into shared repositories unless the abstraction is demonstrably context-neutral.
4. Do not reintroduce page-local frontend query, formatting, or mutation helpers where shared primitives already exist.

## Interface/Contract Changes

1. Gateway webhook methods migrate from `array` payloads to typed payload boundaries (`JsonPayload` / typed DTO).
2. Filter contracts migrate to `*FilterDto` inside `app/Application/<Domain>/Dto/*`.
3. `UpdateAdminOrderStatusInputDto` migrates from raw strings to enum-based fields.
4. Application handlers gradually migrate from ORM returns to typed result DTO.
5. Shipping webhook gets async ingestion parity (`ProcessShippingWebhookJob`).
6. Additive canonical account read routes are allowed if `/api/v1/*` envelope stays `data/meta/error`.
7. Additive selector/read-model endpoints are allowed for admin forms when they remove coupling to management-list payloads.
8. Cleanup execution may adopt additive config and index changes if existing command semantics stay backward-compatible.
9. `CatalogIndexRequest` FormRequest introduced for `CatalogController` catalog list validation (replaces inline `$request->validate()`).
10. `ResolvesAuthenticatedUser` trait introduced for shared `resolveCurrentUser()` in API V1 controllers.
11. Canonical `formatPrice` frontend utility centralized in `resources/js/utils/format.ts` (replaces 4+ divergent raw implementations).
12. Internal read-model boundaries split into `AdminOrderReadRepository`, `AdminProductReadRepository`, and `CatalogProductReadRepository`; account-order summary semantics move to `AccountOrderSummaryProjector`.

## Wave Dependency Notes

1. Wave 15 should precede Wave 18 because account-order extraction defines the correct account read boundary before repository splitting.
2. Wave 16 should precede further admin product/category form cleanup because selector decoupling creates the shared primitive those flows should consume.
3. Wave 24 repository guardrails must encode the bounded repositories introduced by Wave 18 rather than transitional wrappers.
4. Waves 21 and 22 are secondary strengthening waves and should not block Waves 15-19 while domain-boundary work is active.
5. Wave 23 should start after the major boundary splits stabilize; otherwise static-analysis hardening will churn around moving targets.

## Implementation Waves

### Wave 0 (1 day) - Governance Reset

1. Set this file as active architecture execution source.
2. Keep historical plans as history, not active source-of-truth.
3. Keep `docs/REFACTORING_EXECUTION_PLAN.md` as operational log only.

DoD:

- no contradictory active plans;
- one current architecture roadmap in execution.

### Wave 1 (2-3 days) - Transport Purity Completion

1. Add application handlers for webhook ingress:
   - `EnqueuePaymentWebhookHandler`
   - `EnqueueShippingWebhookHandler`
2. Keep webhook controllers transport-only:
   - header validation,
   - command dispatch,
   - standardized response.
3. Add async shipping ingestion job (`ProcessShippingWebhookJob`) for parity.
4. Move Password/Verification orchestration to application handlers.
5. Extend public controller architecture test coverage to all API V1 controllers.

DoD:

- API V1 controllers have application-handler dependencies only (except documented reversible exceptions).

### Wave 2 (3-4 days) - Webhook Contract Hardening

1. Replace gateway webhook `array` params with typed payload boundaries.
2. Remove duplicated parse/verify logic across controller/adapter/job.
3. Centralize webhook outcome taxonomy and error mapping.
4. Add shipping webhook API contract scenario to `app:api-contract-smoke`.
5. Add parity feature tests for payment/shipping: signature, duplicate, payload hash mismatch, retry safety.

DoD:

- payment and shipping webhook ingestion share the same architectural pattern.

### Wave 3 (3-5 days) - DTO Discipline Reconciliation

1. Migrate `app/Filters/*` to application-layer `*FilterDto`.
2. Migrate `UpdateAdminOrderStatusInputDto` to enum fields.
3. Ensure `toArray()` boundaries produce scalar-safe transport payloads.
4. Remove dead code and add guardrail against unused legacy payload builders.

DoD:

- DTO naming/placement matches ADR across backend/frontend.

### Wave 4 (1-2 weeks) - Service Decomposition

1. Split `CheckoutService` into focused components:
   - idempotency,
   - inventory reservation,
   - discount resolution,
   - order write orchestration.
2. Split `CartService` into resolver/mutation/result-mapper services.
3. Extract status transition policies for order/payment/shipment.
4. Cover transition matrices and concurrency paths with unit tests.

DoD:

- critical services reduced to orchestration-level complexity;
- status transitions defined in dedicated policy classes.

### Wave 5 (1 week) - Application Boundary Hardening

1. Replace ORM/paginator returns in handlers with typed result DTOs (incremental by domain).
2. Add architecture test: forbid ORM return types in `app/Application/*Handler`.
3. Move auth persistence/query responsibilities behind repository contracts.

DoD:

- application layer no longer leaks ORM types to transport layer.

### Wave 6 (4-5 days) - Frontend Structural Consolidation

1. Extract duplicated route-query logic for admin lists into shared schema-driven helpers. (`completed`)
2. Decompose large admin composables into state/query/mutation/view-model slices. (`completed`, expanded with admin orders + promotions + categories + products query state-slice extraction and products/promotions/categories mutation decomposition)
3. Add deterministic tests for route sync, cancellation, and out-of-order responses. (`completed`, expanded with products route-sync list coverage, admin-order detail race guard, and route-sync duplicate-reload suppression coverage)

DoD:

- repeated logic (`>=2` usage) extracted to shared modules;
- admin composables have clear responsibilities.

### Wave 7 (3-4 days) - Observability Modularization

1. Split `ObservabilityService` into ingestion/store/snapshot modules.
2. Split `ObservabilityAlertRouter` into channel senders behind a shared interface.
3. Add unit/feature tests for cooldown, fallback routing, and snapshot correctness.

DoD:

- observability module has explicit internal boundaries and lower coupling.

### Wave 8 (2-3 days) - Operations Hardening

1. Extract cleanup retention policy from `app:maintenance-cleanup`.
2. Extract on-call drill plan and escalation matrix from `app:oncall-drill-smoke`.
3. Normalize operational docs and README references to active architecture source-of-truth.
4. Add guardrail tests for cleanup validation and on-call write-smoke plan composition.

DoD:

- maintenance/on-call commands are orchestration-only;
- operational docs point to the active roadmap;
- lifecycle and drill configuration paths are covered by deterministic tests.

### Wave 9 (2-3 days) - Governance and Operational Guardrails

1. Add architecture tests for operational command boundaries:
   - `app:maintenance-cleanup`,
   - `app:oncall-drill-smoke`,
   - `app:observability-alert-check`,
   ensuring commands remain orchestration-only over explicit services/policies.
2. Add config/runbook consistency guardrails:
   - assert README and operational runbooks reference `docs/ARCHITECTURE_REFACTOR_NEXT.md` as the active source-of-truth;
   - assert critical operational config keys required by cleanup/on-call/observability flows are present and typed.
3. Add lifecycle/alert regression coverage for scheduler-safe paths:
   - cleanup dry-run vs apply behavior,
   - alert cooldown/fallback channel wiring,
   - command exit-code stability for on-call/observability smoke flows.
4. Remove residual low-value frontend/admin duplication only where a shared primitive already exists and extraction is net-negative to postpone.

DoD:

- operational commands have architecture guardrail coverage;
- operational docs/config drift is caught automatically;
- lifecycle and alert flows have deterministic regression coverage for scheduler/on-call execution paths.

### Wave 10 (2-3 days) - Observability Report Command Modularization

1. Extract option parsing/validation from `app:observability-report`.
2. Extract threshold and required-sample evaluation from the command body.
3. Extract output shaping for table/json paths into dedicated support boundary.
4. Add unit/feature tests for option parsing, evaluation, JSON path, and disabled-observability warning path.
5. Add `app:observability-report` to operational command architecture guardrails.

DoD:

- `app:observability-report` is orchestration-only;
- option parsing/evaluation/rendering live in explicit support boundaries;
- command behavior is covered by deterministic unit and feature tests;
- operational command guardrails include report command too.

### Wave 11 (3-4 days) - Smoke Command Scenario Modularization

1. Decompose large smoke commands into explicit scenario runners:
   - `app:api-contract-smoke`,
   - `app:webhook-flow-smoke`,
   - `app:performance-smoke`.
2. Extract scenario matrices, setup builders, and result presenters from command bodies.
3. Add architecture guardrails for smoke commands so orchestration stays out of command classes.
4. Add deterministic tests for scenario selection, failure aggregation, and production-safe rollback/persist modes.

DoD:

- smoke commands become orchestration-only over scenario runners;
- scenario setup/evaluation/presentation are explicit and testable;
- production-safe smoke execution paths have regression guardrails.

### Wave 12 (2-3 days) - Shared Smoke Infrastructure Consolidation

1. Extract shared rollback-policy and selective-scenario execution primitives reused by smoke commands.
2. Normalize smoke output/result DTO conventions across API contract, webhook, and performance commands.
3. Add docs/runbook coverage for targeted smoke execution in local and production-safe modes.
4. Add guardrails for smoke scenario ordering/contracts so future scenarios remain additive and explicit.

DoD:

- shared smoke infrastructure reuse is explicit instead of per-command duplication;
- targeted smoke execution semantics are documented and stable;
- smoke support boundaries have contract guardrails in tests/docs.

### Wave 13 (2-3 days) - Command Contract and Scheduler Guardrails

1. Add guardrails for operational/smoke command signatures so documented flags stay aligned with console entrypoints.
2. Add scheduler-wiring guardrails for smoke/report/cleanup/on-call command registration and expected cadence intent.
3. Consolidate any remaining duplicated command-output or nested-runner plumbing only where shared primitives already exist.
4. Add deterministic regression coverage for command failure aggregation and scheduler-safe exit-code semantics across smoke/operational entrypoints.

DoD:

- command signatures and docs/examples cannot silently drift;
- scheduler registration for critical operational entrypoints is guarded by tests;
- remaining command-shell semantics are explicit and regression-covered.

### Wave 14 (2-3 days) - Release and CI Guardrails

1. Add guardrails for README/ops/release checklist parity around quality-gate and smoke command sequences.
2. Add contract tests for release-readiness documents that still reference historical command sets or outdated roadmap files.
3. Harden CI/release support scripts and docs so smoke/report command usage stays aligned with the active architecture roadmap.
4. Close any remaining low-yield duplicated command references only where a shared manifest or guardrail already exists.

DoD:

- release-readiness docs and CI guidance cannot silently drift from executable command contracts;
- quality-gate and production-smoke instructions are regression-guarded;
- remaining operational documentation debt is reduced below architectural concern.

### Wave 15 (4-6 days) - Account Orders Module Extraction And Read-Model Split

1. Extract authenticated account-order transport from `CheckoutController` into a dedicated account orders controller namespace.
2. Extract `resolveCurrentUser()` from `CheckoutController` and `CartController` into a shared `ResolvesAuthenticatedUser` trait reused by both controllers and the new account controller.
3. Add additive canonical account routes:
   - `GET /api/v1/account/orders`,
   - `GET /api/v1/account/orders/{order}`,
   - `GET /api/v1/account/orders/summary`;
   while preserving legacy aliases:
   - `GET /api/v1/orders/me`,
   - `GET /api/v1/orders/me/summary`.
4. Create explicit account order query handlers and repository contract:
   - `ListAccountOrdersQuery/Handler`,
   - `GetAccountOrderDetailQuery/Handler`,
   - `GetAccountOrdersSummaryQuery/Handler`,
   - `AccountOrderReadRepository`.
5. Split account payloads into summary/detail DTO boundaries instead of returning detail-heavy list items from checkout DTOs.
6. Migrate frontend account orders to:
   - summary list + lazy detail loading,
   - explicit account order status types,
   - shared schema-driven route query helpers instead of bespoke account-only parsing.
7. Add deterministic tests for:
   - summary list payload shape,
   - lazy detail ownership/auth checks,
   - stale detail response suppression,
   - legacy alias backward compatibility,
   - `useAccountOrdersViewModel` composable coverage.

DoD:

- account order read concerns no longer live in checkout transport/application namespaces;
- list payload is summary-only and detail is explicit;
- `resolveCurrentUser` duplication eliminated via shared trait;
- account route query and route sync use shared abstractions instead of bespoke copies.

### Wave 16 (3-4 days) - Category Selector Decoupling And Shared Option Query

1. Add dedicated selector endpoint for admin category options:
   - `GET /api/v1/admin/categories/options`.
2. Introduce `AdminCategoryOptionResultDto` and a selector-specific query handler instead of reusing paginated management-list payloads.
3. Create a shared frontend category-options API/composable used by:
   - admin category parent selector,
   - admin product category selector.
4. Remove dependency on page-local list data for `parentOptions`.
5. Remove implicit selector coupling to `per_page=200` management-list behavior.
6. Add deterministic tests for:
   - selector option ordering,
   - `exclude_id` handling,
   - parent/self-exclusion behavior,
   - shared reuse across category and product forms.

DoD:

- admin form selectors no longer depend on paginated list contracts;
- category option loading is centralized and context-specific.

### Wave 17 (3-5 days) - Maintenance Cleanup Resource Strategy And Scale Safety

1. Split cleanup internals into resource-specific boundaries:
   - `CheckoutIdempotencyCleanupResource`,
   - `WebhookReceiptCleanupResource`,
   - `ActiveCartCleanupResource`,
   - `InactiveCartCleanupResource`.
2. Add `MaintenanceCleanupPlanFactory` so retention/cutoff policy and ordered cleanup plan are explicit.
3. Reduce `MaintenanceCleanupExecutor` to orchestration-only execution over typed resources/plan.
4. Add additive cleanup config:
   - `cleanup.batch_size`.
5. Replace unbounded destructive deletes with deterministic batched deletion.
6. Add additive index support for cleanup predicates:
   - `carts(status, updated_at)`,
   - `webhook_receipts(created_at)`.
7. Expand tests for:
   - dry-run vs apply,
   - batched deletion accounting,
   - cutoff policy correctness,
   - index/config guardrails.

DoD:

- adding a new cleanup resource does not require modifying core executor logic;
- cleanup predicates are supported by explicit indexes and bounded execution strategy.

### Wave 18 (4-6 days) - Read Repository Boundary Split And Product Write Decomposition

1. Split mixed read repositories by context:
   - `OrderRepository` into account/admin read boundaries,
   - `ProductRepository` into catalog/admin read boundaries.
2. Extract `OrderRepository::summaryForUser()` domain-semantic logic ("paid" status interpretation, "in delivery" status interpretation) into an explicit account-order summary policy or service so the repository contains only data retrieval.
3. Extract duplicated search filter logic (`order_number LIKE / email LIKE`) into a shared order search scope or builder method reused by both account and admin repositories.
4. Keep shared helpers only when they are truly context-neutral; otherwise preserve explicit duplication over hidden coupling.
5. Decompose `AdminCatalogService` into explicit collaborators for:
   - variant resolution,
   - variant sync,
   - inventory sync,
   - price sync,
   while keeping transaction orchestration explicit.
6. Add architecture tests forbidding mixed account/admin/catalog read responsibilities in one repository class.
7. Add architecture guardrail for repository layer: no domain-semantic status interpretation inside repository methods.
8. Add unit coverage for the extracted admin product write collaborators and SKU collision rules.

DoD:

- read repositories are bounded by domain context;
- repository layer contains no business-logic status interpretation;
- admin product writes are orchestration over explicit collaborators, not a single mixed service.

### Wave 19 (2-3 days) - Infrastructure Provider Hygiene And Container Guardrails

1. Split `AppServiceProvider` by concern into dedicated provider modules for:
   - auth bindings,
   - gateway bindings,
   - observability bindings.
2. Keep the remaining app-level provider narrow and bootstrap-only.
3. Add container/architecture guardrails so heterogeneous bindings do not silently drift back into one global provider.
4. Preserve existing driver resolution and alert-router semantics while moving them behind specialized providers.

DoD:

- infrastructure bindings are grouped by concern;
- the global app provider no longer acts as a mixed container hotspot.

### Wave 20 (1-2 days) - Archive And Documentation Hygiene

1. Add guardrails so archived plans keep explicit archival banners and cannot silently become active source references again.
2. Normalize residual operational docs issues:
   - stale numbering,
   - duplicated command examples,
   - historical references that should point to canonical aliases or the active roadmap.
3. Add regression checks for deploy/release helper docs so support scripts, checklists, and runbooks stay consistent.
4. Remove only low-yield duplicated documentation fragments where canonical aliases already exist.

DoD:

- archived plans remain explicitly non-authoritative;
- operational/release docs have deterministic parity guardrails;
- residual documentation drift drops below roadmap significance.

### Wave 21 (2-3 days) - Transport Validation Consistency

1. Create `CatalogIndexRequest` FormRequest for `CatalogController::index()` to achieve parity with all other API V1 controllers that already use dedicated FormRequest classes.
2. Evaluate extraction of `Idempotency-Key` header validation from `CheckoutController::placeOrder()` into a dedicated middleware or FormRequest concern so idempotency-key enforcement is not inline transport logic.
3. Add architecture guardrail: forbid inline `$request->validate()` calls in API V1 controllers; enforce dedicated FormRequest usage.

DoD:

- all API V1 controllers use dedicated FormRequest classes for validation;
- architecture guardrail prevents regression to inline validation.

### Wave 22 (2-3 days) - Frontend Price Formatting And Utility Consolidation

1. Extract canonical `formatPrice(value, currency?)` utility into `resources/js/utils/format.ts` based on existing `formatMoney` function.
2. Replace raw `Number(value).toFixed(2)` duplication with the shared utility in:
   - `resources/js/components/cart/CartSummaryHeader.vue`,
   - `resources/js/pages/CatalogPage.vue`,
   - `resources/js/pages/ProductPage.vue`,
   - `resources/js/components/cart/CartItemsTable.vue`.
3. Normalize `formatPrice` usage in view-models that already use `formatMoney` to go through the shared utility for consistency.
4. Add missing composable test coverage for:
   - `useCatalogProducts`,
   - `useCatalogProduct`,
   - `useCheckoutPageViewModel`,
   - `useAuthPageViewModel`.
5. Assess `CartItemsTable` / `OrderItemsTable` structural similarity for potential shared table component extraction (extract only if shared component is net-positive).

DoD:

- `formatPrice` has a single canonical definition;
- raw `toFixed(2)` duplication eliminated from components and pages;
- catalog/checkout/auth composable test coverage added.

### Wave 23 (3-5 days) - Static Analysis Hardening

1. Audit and categorize the static-analysis debt by source group (model generics, dead transport resources, gateway contracts, command-feature assertions, object-shape access).
2. Remove stale suppression debt by fixing or deleting resolved code paths instead of maintaining ignores.
3. Tighten resolvable type issues incrementally by domain group (model generics, transport artifacts, gateway contracts, typed helpers for payment and order state checks).
4. Remove `phpstan-baseline.neon` entirely if level 6 can be kept green without it.
5. Evaluate PHPStan level 7 feasibility and document the remaining blocker set for a future upgrade.

DoD:

- PHPStan level 6 runs green without `phpstan-baseline.neon`;
- stale suppression debt is removed rather than re-baselined;
- level 7 blockers are measured and explicitly documented.

### Wave 24 (2-3 days) - Architecture Guardrail Expansion

1. Add repository layer boundary guardrail:
   - forbid domain-semantic status interpretation, aggregate computation, or business-rule evaluation inside repository methods;
   - enforce that repository methods return data structures, not business decisions.
2. Add jobs/listeners afterCommit discipline guardrail:
   - verify that side-effect listeners dispatching jobs use `afterCommit` or queue-safe patterns;
   - verify that job classes implement idempotency-safe patterns where applicable.
3. Add policy completeness matrix guardrail:
   - assert all models with route model binding have corresponding Gate policy mappings;
   - assert policy matrix covers expected CRUD actions.

DoD:

- three new architecture guardrail tests are enforced;
- repository, job/listener, and policy boundaries are protected from silent regression.

## Program Exit Targets

1. No mixed-context repositories remain across account, admin, and catalog read paths.
2. No inline validation remains in API V1 controllers.
3. Shared authenticated-user resolution is centralized for API V1 controllers that need it.
4. Category selectors are decoupled from management-list contracts.
5. Account orders follow explicit summary/detail read-model parity with admin patterns.
6. Maintenance cleanup uses explicit resource strategy and bounded execution.
7. A single canonical frontend price-formatting utility is used across catalog, cart, account, and admin flows.
8. PHPStan runs clean at level 10 without a baseline file.
9. Architecture guardrails cover controllers, handlers, repositories, jobs/listeners afterCommit discipline, and policy completeness.

## Backlog Intake Rule

1. `docs/DEEP_ARCHITECTURE_AUDIT_2026_03.md` is an aligned backlog input for the next execution program, not an active execution authority.
2. Findings from that audit remain candidate backlog only until they are explicitly promoted into this file as new waves or backlog blocks.
3. Promotion order should preserve the current architecture-first sequence:
   - safety and locking;
   - backend boundary quick wins;
   - frontend consistency;
   - deep domain expansion;
   - platform enablement.
4. Deep domain items such as `Money`, `app/Domain`, checkout orchestrator expansion, and broader domain-event rollout require separate approval and must not be bundled into quick-win slices.

## Mandatory Test Matrix

1. Architecture tests:
   - full API V1 controller boundary coverage,
   - no regression to array-based contracts,
   - no ORM return leakage in handlers (after Wave 5),
   - no inline `$request->validate()` in API V1 controllers (after Wave 21),
   - repository layer boundary: no domain-semantic status interpretation (after Wave 18/24),
   - jobs/listeners afterCommit discipline (after Wave 24),
   - policy completeness matrix for route-bound models (after Wave 24).
2. Feature tests:
   - webhook parity and idempotency,
   - admin status transition validation,
   - account order summary/detail contract parity,
   - category selector endpoint behavior,
   - maintenance cleanup dry-run/apply behavior,
   - payload hash mismatch and signature failures.
3. Unit tests:
   - transition policies,
   - decomposed checkout/cart components,
   - observability modules,
   - cleanup resource strategy,
   - repository/provider guardrails,
   - account order summary policy (domain-semantic status interpretation),
   - `formatPrice` shared utility.
4. Frontend tests:
   - route-query schema helpers,
   - composable race/cancellation guarantees,
   - API contract assertions,
   - account lazy detail loading,
   - shared category option reuse,
   - catalog/checkout/auth composable coverage (`useCatalogProducts`, `useCatalogProduct`, `useCheckoutPageViewModel`, `useAuthPageViewModel`).
5. Smoke tests:
   - `app:api-contract-smoke` includes shipping webhook contract,
   - `app:webhook-flow-smoke` remains green with idempotent replay.

## Quality Gate (Strict Sequence)

1. `composer run lint`
2. `composer run analyse`
3. `php artisan test`
4. `npm run lint`
5. `npm run lint:ox`
6. `npm run format:ox:check`
7. `npm run type-check`
8. `npm run test`
9. `npm run build`
10. if routes/controllers changed:
   - `php artisan optimize:clear`
   - `php artisan route:list --path=api/v1/admin/promotions`

## Assumptions and Defaults

1. Priority mode is `Architecture-first`.
2. Public API envelope must remain stable.
3. Any webhook status-code normalization is an explicit later migration.
4. Each completed logical block updates `docs/REFACTORING_EXECUTION_PLAN.md` with executed checks.
5. No block is considered complete until full quality gate is green.
6. Additive API/read-model endpoints are allowed if the public envelope stays backward-compatible.
7. Legacy account aliases remain until a separate approved deprecation/removal plan exists.
