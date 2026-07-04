# Wave C0 — Module Boundary Foundation

**Status:** Active — execution starts after this plan is approved.
**Scope owner:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` → Convergence Waves → C0.
**Sequence constraint:** Entry criteria met (`R1` and `S1` closed). C0 is the prerequisite for every later wave C1-C7.

## Verified baseline (`2026-07-05`, mapping done by explore agent)

- `app/Domains/*` exists as 7 README-only directories (`Catalog`, `Cart`, `Checkout`, `Orders`, `Users`, `Payments`, `Webhooks`), pinned by `ModularMonolithSkeletonGuardrailTest`. Zero PHP files.
- `app/Domain/*` (singular) is the **shared domain kernel**: 4 typed exceptions (`Cart/Checkout/OrderTransition/OrderStaleAggregate` Exception), `StatusTransitionSource` enum, `OrderPaymentStatusResolver`, `Money` value object. Cross-module imports from here MUST stay allowed; otherwise the migration blocks immediately.
- Existing layer-direction guardrail: `LayerDependencyDirectionGuardrailTest.php` does literal-substring matching (not namespace-aware, not module-aware). C0 must NOT rewrite it (shrink-only allowlist policy); C0 adds a new namespace-aware `ModuleBoundaryGuardrailTest` that operates on `app/Domains/*`.
- Existing `Contracts` convention: `Application/<Context>/Contracts/` is already in use for Auth, Admin (per sub-context), Catalog, Account. Cart, Checkout, Webhook currently expose cross-context surface through root `app/Contracts/*Interface.php` (5 interfaces, returning Eloquent models `Cart`/`Order`).
- Cross-context coupling today: `Application/<X>Handler → App\Services\<Y>` is the dominant pattern (concrete class, not contract). Application→Application DTO coupling across contexts is **already zero** — the precedent C0 must lock in.
- Composer autoload: `App\` → `app/` only; `App\Domains\` not declared separately (not required for C0; covered by the `App\` prefix).

## Resolved design decisions (`2026-07-05`)

1. **Module public API = `app/Domains/<Module>/Contracts/` (interfaces + DTOs only).** No Eloquent models at the contract boundary — DTOs/scalars/value-objects only. This is the stricter rule, consistent with `ApplicationHandlerBoundaryTest`, and prevents model-coupling from leaking across modules.
2. **`App\Domain\*` (singular) = shared domain kernel.** Cross-module imports from `App\Domain\*`, `App\Support\*`, `Illuminate\*`, and PHP/stdlib are always allowed. The guardrail allowlists these explicitly.
3. **Legacy root `app/Contracts/*` stays in place** until each module owns its own contract surface (C1-C7). The guardrail treats `App\Contracts\*` as a legacy cross-cutting namespace, not a module — it is allowlisted until its contents migrate. This avoids a big-bang rewrite and lets C1-C7 relocate one interface at a time.
4. **`app/Application/*`, `app/Services/*`, `app/Repositories/*` are NOT yet modules.** The C0 guardrail only governs `app/Domains/*`. The existing layer-direction rules continue to govern the legacy directories until each slice moves.
5. **Relocation mechanics: namespace move per slice, no dual-namespace compatibility shims, no `class_alias`.** When C1 moves `CatalogController` into `app/Domains/Catalog/Controllers/`, its namespace changes from `App\Http\Controllers\...` to `App\Domains\Catalog\Controllers\...` in the same block. Route definitions, DI bindings, and tests update atomically. No shim layer.
6. **Provider re-registration policy: each module ships a `<Module>ServiceProvider` that binds its Contracts to implementations.** The provider is registered in `bootstrap/providers.php` (or `config/app.php` depending on Laravel version). C0 does NOT add providers (no module has runtime code yet); C1+ adds one provider per moving slice.
7. **No new composer autoload root for C0.** `App\` → `app/` already covers `App\Domains\*`. A separate `App\Domains\` PSR-4 root is a future decision tied to potential composer-package extraction, not required for the in-app modular monolith.

## Invariants to lock

1. **Cross-module imports go through `Contracts` only.** Within `app/Domains/<Module>/`, an import of `App\Domains\<OtherModule>\` is allowed only when the imported namespace is `<OtherModule>\Contracts\`. Importing `<OtherModule>\Services\`, `<OtherModule>\Controllers\`, `<OtherModule>\Repositories\`, `<OtherModule>\Models\`, or `<OtherModule>\Application\` is forbidden.
2. **Module-internal layer direction still applies.** Within `app/Domains/<Module>/`, the existing rules carry over: Controllers don't depend on Services/Repositories directly; Application handlers depend on Contracts/DTO/Domain; Services depend on Domain + Repository contracts; Repositories stay persistence-only.
3. **Shared kernel is always reachable.** `App\Domain\*`, `App\Support\*`, and stdlib imports are allowlisted from every module.
4. **Legacy bridge namespace stays allowlisted.** `App\Contracts\*`, `App\Application\*`, `App\Services\*`, `App\Repositories\*`, `App\Http\*`, `App\Models\*` imports from within `app/Domains/*` are allowlisted during the migration (each wave closes one of these bridges). The guardrail's allowlist is documented and **only shrinks** as modules migrate.
5. **Each module declares its public Contracts surface.** A module with zero PHP files (today's state) trivially passes; the guardrail becomes load-bearing with C1.

## Out of scope (deliberate)

- Moving ANY runtime code. C0 is documentation + guardrail + maps only.
- Designing the contract interfaces themselves (e.g., `CatalogProductReadRepository` relocation). That is per-wave work (C1-C7).
- Adding `App\Domains\` as a separate PSR-4 autoload root. Not required for the in-app modular monolith.
- Adding module ServiceProviders. No module has runtime code to bind.
- Removing the legacy root `app/Contracts/*`. Each wave relocates one slice; the root namespace retires post-C7.

## Implementation slices

### Slice 1 — Module Boundary Contract documentation in `ARCHITECTURE.md`

- Extend the "Modular Monolith Target Layout" section in `docs/ARCHITECTURE.md`:
  - Add `Contracts` to the per-module subfolder list (currently lists only `Controllers/Services/Repositories/Models`).
  - Add a `## Module Boundary Contract` subsection declaring: (a) `app/Domains/<Module>/Contracts/` is the module's public API (interfaces + DTOs only, no Eloquent models); (b) cross-module imports allowed only to another module's `Contracts` namespace; (c) `App\Domain\*` is the shared domain kernel (cross-module-allowed); (d) `App\Contracts\*` legacy root stays until each module owns its own surface; (e) relocation mechanics: namespace move per slice, no shims, no `class_alias`, atomic with route/DI/test updates; (f) provider re-registration policy (`<Module>ServiceProvider` per module from C1 onward).
- **Smoke:** `ModularMonolithSkeletonGuardrailTest` still passes (it asserts the `## Modular Monolith Target Layout` section exists in `ARCHITECTURE.md`).

### Slice 2 — `ModuleBoundaryGuardrailTest` (the load-bearing artifact)

- Create `tests/Unit/Architecture/ModuleBoundaryGuardrailTest.php`. Assertions:
  1. `app/Domains/*` exists with the 7 named modules (compose with skeleton guardrail, don't duplicate).
  2. **Cross-module imports through Contracts only**: scan every PHP file under `app/Domains/<Module>/`; any `use App\Domains\<OtherModule>\` import must match the pattern `App\Domains\<OtherModule>\Contracts\`. Violations fail with a message naming the offending file:line and the imported namespace.
  3. **Module-internal layer direction** (within `app/Domains/<Module>/`, once subfolders exist): controllers don't import Services/Repositories directly; etc. (Mirror `LayerDependencyDirectionGuardrailTest` rules scoped to the module.)
  4. **Allowlist is documented and enumerable**: a constant array `LEGACY_BRIDGE_NAMESPACES` lists the allowlisted cross-cutting prefixes (`App\Domain\`, `App\Support\`, `App\Contracts\`, `App\Application\`, `App\Services\`, `App\Repositories\`, `App\Http\`, `App\Models\`, `Illuminate\`). The test asserts this list matches the documented allowlist (so allowlist growth is visible in code review and fails the test if silently widened).
- Helper: namespace-aware file scanner (parse `use` statements, not literal substring). Reuse the `assertDirectoryFilesDoNotContain` pattern from `LayerDependencyDirectionGuardrailTest` but operate on parsed use-statements.
- **Smoke:** guardrail passes trivially today (empty `app/Domains/*`); becomes load-bearing with C1.

### Slice 3 — Update `REPO_MAP.md` and `DOMAIN_MAP.md`

- `docs/REPO_MAP.md`: extend the "Target layout" section with per-module ownership table (`Module | Public API (Contracts) | Owning wave | Migration state`). Initial migration state for all 7 modules: `pending (C1-C7)`.
- `docs/DOMAIN_MAP.md`: add a migration-state marker to each context's H3 section (`Catalog → [migration: pending C1]`, `Users → [migration: pending C2]`, etc.). Add a `## Module Boundary Contract` cross-reference to `ARCHITECTURE.md`.

### Slice 4 — Guardrail + docs sync (roadmap, execution plan)

- `docs/ARCHITECTURE_REFACTOR_NEXT.md`: mark `C0` Closed, append a closed-block definition (with convergence impact: the boundary is load-bearing before any runtime code moves), update Risk Register (no new entries expected; the cross-context coupling findings from the explore agent become the contract-design input for C1-C7), Exit Targets (#25 — module-boundary guardrail active — moves to achieved), Change Control.
- `docs/REFACTORING_EXECUTION_PLAN.md`: append entry with verified baseline, slice-by-slice decisions, the documented allowlist, and the quality gate result.

### Slice 5 — Quality gate

- Run the canonical sequence strictly in order, one command at a time:
  1. `composer run lint`
  2. `composer run analyse`
  3. `php artisan test`
  4. `npm run lint`
  5. `npm run lint:ox`
  6. `npm run format:ox:check`
  7. `npm run type-check`
  8. `npm run test`
  9. `npm run build`
- Scope note: backend changed (tests/, docs/) → all backend steps run; frontend untouched → frontend steps run for regression safety.

## Definition of done

1. `docs/ARCHITECTURE.md` declares the module boundary contract (public API = Contracts/, no Eloquent at boundary, cross-module imports through Contracts only, shared kernel allowlist, relocation mechanics, provider policy).
2. `ModuleBoundaryGuardrailTest` is active and green (passes trivially today, becomes load-bearing with C1).
3. `REPO_MAP.md` and `DOMAIN_MAP.md` carry per-module ownership and migration-state markers.
4. `docs/ARCHITECTURE_REFACTOR_NEXT.md` records `C0` as Closed; `docs/REFACTORING_EXECUTION_PLAN.md` records the executed work and checks.
5. The quality gate is green and the executed checks are reported.
6. No runtime code moves (controllers, services, repositories untouched); C0 is foundation only.
