# Q4 — Frontend Type And Test-Signal Hardening

**Status:** Active — execution starts after this plan is approved.
**Scope owner:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` (item 12 in Execution Queue, P2).
**Source finding:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` → Promoted Quality/Reliability Blocks → Q4.

## Verified baseline (`2026-07-04`)

- `tsconfig.json` declares `"strict": true` only. Missing: `noImplicitOverride`, `noFallthroughCasesInSwitch`, `noUncheckedIndexedAccess`. `moduleResolution: "Bundler"` is appropriate for Vite; no path inconsistency.
- `vitest.config.ts` runs `jsdom` with no coverage configuration. `package.json` `test` script is `vitest run` (no `--coverage`).
- `@vitest/coverage-v8` is not in `devDependencies`. Vitest 4.x requires the explicit v8 provider package for `--coverage`.
- Current test count: 306 passed across 85 files (per last quality gate run).

## Invariants to lock

1. **TypeScript strictness ladder.** Add `noImplicitOverride` (forces `override` keyword on inherited members) and `noFallthroughCasesInSwitch` (forbids fall-through in switch). Both are low-churn, high-signal flags that surface real bugs.
2. **`noUncheckedIndexedAccess` is gated on measured churn.** Enable only if the surfaced fixes are bounded and architectural; otherwise document the blocker count and defer.
3. **Coverage signal, not coverage floor.** Add v8 coverage reporting as a non-blocking CI artifact. A coverage floor is a separate decision after baseline observation; this block does not introduce one.
4. **No test behavior changes.** The hardening is type-only and tooling-only; runtime behavior of components, composables, and stores stays identical.

## Out of scope (deliberate)

- Coverage floor / threshold enforcement.
- Migration to a different test runner or assertion library.
- Component-level restructuring to satisfy the new flags; fixes stay minimal (add `override`, narrow indexed-access types, etc.).
- Frontend ESLint rule changes (the lint gate is already green; this block is tsconfig + vitest only).

## Implementation slices

### Slice 1 — Enable low-churn strict flags

- Add `noImplicitOverride` and `noFallthroughCasesInSwitch` to `tsconfig.json` `compilerOptions`.
- Run `npm run type-check`. Fix surfaced issues by adding `override` keyword or restructuring switch cases minimally.
- **Smoke:** `npm run type-check` exits 0.

### Slice 2 — Measure `noUncheckedIndexedAccess` churn — Deferred (`2026-07-04`)

- Added the flag temporarily and ran `npm run type-check`. **Total: 55 errors.**
- Distribution:
  - **2 production-code errors** (in `resources/js/components/admin/products/AdminProductVariantsSection.vue` line 17, `resources/js/composables/admin/orders/useAdminOrderDetailsState.ts` line 44). Both are narrowable (`ProductVariantForm | undefined` and `StatusDraft | undefined` from indexed access into filtered collections).
  - **53 test-only errors** concentrated in `tests/composables/use-admin-mutation-flows.spec.ts` (21), `tests/components/admin/admin-component-contracts.spec.ts` (11), `tests/components/auth/auth-component-contracts.spec.ts` (5), and four other test files. They are mostly indexed access into fixture arrays or `Object.keys(...)` enumeration where the runtime shape is known but the static type widens to `T | undefined`.
- **Decision: defer.** The 2 production fixes do not justify 53 test-only fixes that risk introducing `!` assertions or `as` casts to work around legitimate test-fixture convenience. Per Q4 DoD, the blocker set is documented for a later block (candidate: a dedicated `Q4-followup` that tightens test fixtures to typed factories before enabling the flag).
- **Risk:** the 2 production cases remain real (potential `undefined` runtime access). Mitigation: they live in admin-only code paths where the upstream filter guarantees presence at runtime; tracked in risk register (#23) for the follow-up block.

### Slice 3 — V8 coverage reporting

- Add `@vitest/coverage-v8` to `devDependencies` (version aligned with vitest `^4.0.18`).
- Update `vitest.config.ts` with a `coverage` block: provider `v8`, reporter `['text', 'html']`, reports directory `coverage/`, `all: true` (so untested files appear), no thresholds.
- Add a `test:coverage` script to `package.json`: `vitest run --coverage`.
- Add `coverage/` to `.gitignore`.
- **Smoke:** `npm run test:coverage` produces a report; `npm run test` still works without coverage.

### Slice 4 — Guardrail + docs sync

- New `tests/Unit/Architecture/FrontendTypeAndTestSignalGuardrailTest.php` (or extend an existing frontend guardrail if one exists) asserts: `tsconfig.json` declares `noImplicitOverride` and `noFallthroughCasesInSwitch`; `noUncheckedIndexedAccess` is either present or absent per the Slice 2 decision; `vitest.config.ts` declares the coverage provider; `package.json` has `test:coverage` script and `@vitest/coverage-v8` in devDependencies; `.gitignore` excludes `coverage/`.
- `docs/ARCHITECTURE_REFACTOR_NEXT.md`: mark `Q4` Closed, advance next block to Active, append Closed-block definition, update Risk Register (#7 closed, optional #23 follow-up if `noUncheckedIndexedAccess` deferred), Exit Targets (#23 achieved), Mandatory Test Matrix, Change Control.
- `docs/REFACTORING_EXECUTION_PLAN.md`: append entry with verified baseline, slice-by-slice decisions, fixes applied (if any), and the quality gate result.

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
- Scope note: backend untouched (`composer.json`, `app/`, `routes/`, `tests/` unchanged) → backend steps run for regression safety; the actual hardening surface is `tsconfig.json`, `vitest.config.ts`, `package.json`, and any `resources/js/**` type fixes.

## Definition of done

1. `noImplicitOverride` and `noFallthroughCasesInSwitch` enabled with clean `type-check`.
2. `noUncheckedIndexedAccess` either enabled with clean `type-check` or documented as deferred with the measured blocker set.
3. `@vitest/coverage-v8` installed; `npm run test:coverage` produces a v8 report; `npm run test` is unchanged.
4. `FrontendTypeAndTestSignalGuardrailTest` locks the configuration.
5. `docs/ARCHITECTURE_REFACTOR_NEXT.md` records `Q4` as Closed; `docs/REFACTORING_EXECUTION_PLAN.md` records the executed work and checks.
6. The quality gate is green and the executed checks are reported.
