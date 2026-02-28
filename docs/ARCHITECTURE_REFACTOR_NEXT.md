# Architecture Refactor Next (Architecture-First)

Date: `2026-02-27`
Status: `Active`
Priority mode: `Architecture-first`

## Execution Authority

1. This file is the active architecture execution source-of-truth.
2. Historical plans in `docs/*PLAN*.md` are archival references only and must not be used as active execution authority.
3. `docs/REFACTORING_EXECUTION_PLAN.md` remains an operational execution log only.

## Summary

DTO migration is completed, but architecture still has critical structural gaps:

- high business-logic concentration in large services,
- frontend structural duplication and composable layering debt remain in admin flows,
- incomplete architectural guardrails against regressions.

Goal of this program: close those gaps without breaking `/api/v1/*` response envelope (`data/meta/error`) and with strict quality-gate enforcement after each logical block.

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

## Confirmed Findings

1. Critical large-service overloads tracked by the active roadmap are now closed.
2. Frontend structural debt in admin flows is now reduced below primary architectural concern; remaining items are governance/guardrail level, not boundary-rescue work.
3. Operational command boundaries and source-of-truth drift now have automated guardrails.
4. No large command-shell concentration remains in active operational tooling; remaining work is documentation/archive hygiene and residual operational cleanup, not boundary-rescue work.

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

## Interface/Contract Changes

1. Gateway webhook methods migrate from `array` payloads to typed payload boundaries (`JsonPayload` / typed DTO).
2. Filter contracts migrate to `*FilterDto` inside `app/Application/<Domain>/Dto/*`.
3. `UpdateAdminOrderStatusInputDto` migrates from raw strings to enum-based fields.
4. Application handlers gradually migrate from ORM returns to typed result DTO.
5. Shipping webhook gets async ingestion parity (`ProcessShippingWebhookJob`).

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

### Wave 15 (1-2 days) - Archive and Documentation Hygiene

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

## Mandatory Test Matrix

1. Architecture tests:
   - full API V1 controller boundary coverage,
   - no regression to array-based contracts,
   - no ORM return leakage in handlers (after Wave 5).
2. Feature tests:
   - webhook parity and idempotency,
   - admin status transition validation,
   - payload hash mismatch and signature failures.
3. Unit tests:
   - transition policies,
   - decomposed checkout/cart components,
   - observability modules.
4. Frontend tests:
   - route-query schema helpers,
   - composable race/cancellation guarantees,
   - API contract assertions.
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
