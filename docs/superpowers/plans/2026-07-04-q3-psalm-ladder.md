# Q3 — Psalm Ladder And Scope Parity (via psalm/plugin-laravel)

**Status:** Active — execution starts after this plan is approved.
**Scope owner:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` (item 11 in Execution Queue, P2).
**Source finding:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` → Promoted Quality/Reliability Blocks → Q3.

## Verified baseline (`2026-07-04`)

- `psalm.xml` declares `errorLevel="6"`, `findUnusedBaselineEntry="true"`, `findUnusedCode="false"`. Scope: `app/`, `database/factories`, `database/seeders`. No `routes/` coverage.
- Psalm 6.4.1 runs clean at level 6 (0 errors; 389 info-only issues — the baseline referenced by every prior block).
- Level 5 measurement: **1 error** — `app/Providers/AppServiceProvider.php:35` calls `$this->app->isProduction()`; `Illuminate\Contracts\Foundation\Application` interface does not declare `isProduction()` (the method exists on the concrete `Illuminate\Foundation\Application`). PHPStan passes because of larastan stubs; Psalm has no Laravel plugin.
- Level 4 measurement: **56 errors** — almost all `UndefinedMagicPropertyFetch` on Eloquent-model magic properties (`Promotion::$id`, `::$name`, `::$starts_at`, etc.) because models lack `@property` PHPDoc and Psalm cannot resolve Eloquent `__get`. Secondary cluster: `InvalidDocblock` on `@return BelongsTo<User, $this>` template annotations. Root cause: no Psalm plugin for Laravel (analogous to larastan for PHPStan).
- Project runtime: PHP `^8.4`, Laravel `^12.0`, Psalm `6.4.1`, larastan `^3.9` (PHPStan only). No Psalm Laravel plugin in `composer.json`.

## Invariants to lock

1. **Laravel-aware Psalm.** `psalm/plugin-laravel:^3.0` (compatible with Psalm 6 + Laravel 12) provides Eloquent magic-property resolution, relation generics, `casts()` AST parsing, and migration-aware schema inference — closing the parity gap with larastan.
2. **Scope parity with PHPStan.** Psalm scans `app/`, `routes/`, `database/factories`, `database/seeders` (matching PHPStan scope minus `tests/`, which PHPStan covers through larastan test stubs and Psalm will cover only after `tests/` is promoted in a later block).
3. **Strictness ladder.** Raise `errorLevel` step by step (`6 → 5 → 4`), fixing source typing at each step. No `--set-baseline` accumulation: every level is green by code, not by suppression. `findUnusedBaselineEntry` stays `true`.
4. **Taint analysis stays optional.** The plugin ships taint-analysis capability; this block does not enable it in the default quality gate (it would be a separate promotion with its own DoD). The gate keeps `--no-progress` only.
5. **Source-typing fixes stay architectural.** The level-5 fix (`isProduction()` → `environment('production')`) is a legitimate contract tightening: `environment()` is declared on the `Application` interface, while `isProduction()` is a concrete-only convenience.

## Out of scope (deliberate)

- Psalm level `< 4` (3, 2, 1). Q3 DoD is level 4 or stricter; tighter levels are a separate promotion after level-4 blockers are measured.
- Taint-analysis (`--taint-analysis`) in CI. Plugin enables the capability; activation is a separate decision.
- Psalm coverage of `tests/`. PHPStan already covers tests via larastan; adding `tests/` to Psalm scope is a follow-up once level 4 is stable on production code.
- Wholesale `@property` PHPDoc campaigns across Eloquent models — the plugin resolves magic properties from migrations and `casts()`, so manual annotations become opt-in, not a prerequisite.

## Implementation slices

### Slice 1 — Install plugin and configure scope

**Status: in progress, blocked on PHP runtime upgrade.**

- Add `psalm/plugin-laravel` to `require-dev` in `composer.json` — DONE (constraint set to `^3.14`).
- Bump `vimeo/psalm` constraint from exact `6.4.1` to `^6.16.1` to unlock plugin v3.14 — DONE in `composer.json` (lock file intentionally NOT yet updated).
- Register the plugin in `psalm.xml` (`<plugins><pluginClass class="Psalm\LaravelPlugin\Plugin"/></plugins>`) — DONE.
- Add `<directory name="routes" />` to `<projectFiles>` — DONE.

**Blocker (root cause):** PHP runtime on the dev machine is `8.4.1` (managed via OSPanel). Plugin v3.14 and Psalm 6.16.1 both require PHP `~8.4.3` (or compatible newer). The dev machine's PHP must be upgraded via OSPanel before `composer update` can resolve the new constraints.

**Smoke status with v3.0.5 (the only version compatible with Psalm 6.4.1 + PHP 8.4.1):**
- Plugin activates successfully (memory: 1.3GB, 98.1% type coverage).
- 25 errors at level 6: 23 × `TooManyTemplateParams` on `LengthAwarePaginator<int, Model>` (Laravel 12 generics) + 2 × `InvalidReturnType`/`InvalidReturnStatement` in `ApiContractSmokeContextFactory` (Eloquent `firstOrCreate` return narrowing).
- The paginator template issue is fixed upstream in plugin v3.14 (PR #1082) and the `isProduction()` interface issue is fixed in v3.14.2 (PR #1141).

**Decision (user-approved):** Upgrade PHP runtime first (OSPanel operation by the user), then run `composer update` to land plugin v3.14 + Psalm 6.16.1, then re-measure.

**Resume steps after PHP upgrade:**
1. User: upgrade PHP in OSPanel to 8.4.3+ (or newer 8.4.x).
2. Agent: `composer update` (proxy env cleared).
3. Agent: `./vendor/bin/psalm --clear-cache && ./vendor/bin/psalm --no-progress` — re-measure at level 6 with v3.14.
4. Agent: proceed to Slice 2 (level 5) and Slice 3 (level 4) under the upgraded toolchain.

### Slice 2 — Level 5 source fix

- `app/Providers/AppServiceProvider.php:35`: replace `$this->app->isProduction()` with `$this->app->environment('production')`. The runtime provider is fully bootstrapped, so `environment()` is safe here (unlike config-file usage).
- Raise `errorLevel` from `6` to `5`.
- **Smoke:** `./vendor/bin/psalm --no-progress` exits 0.

### Slice 3 — Level 4 measurement + targeted fixes

- Re-measure level 4 with the plugin active. Expect the `UndefinedMagicPropertyFetch` cluster to collapse (plugin resolves Eloquent properties from migrations).
- Fix any remaining level-4 errors by source typing (expected categories: `InvalidDocblock`, `RedundantCast`, `NoInterfaceProperties`). No baseline file.
- Raise `errorLevel` from `5` to `4`.
- **Smoke:** `./vendor/bin/psalm --no-progress` exits 0.

### Slice 4 — Guardrail + docs sync

- New `tests/Unit/Architecture/PsalmLadderScopeParityGuardrailTest.php` asserts: `psalm.xml` declares `errorLevel="4"` or stricter, scopes `app/`, `routes/`, `database/factories`, `database/seeders`, registers the Laravel plugin, and does NOT declare a `<baseline>` file.
- `docs/ARCHITECTURE_REFACTOR_NEXT.md`: mark `Q3` Closed, advance `Q4` to Active, append Closed-block definition, update Risk Register (Q3 risk: plugin abandonment / version drift — mitigation: pin `^3.0`, track v4/Psalm 7 upgrade as candidate), Exit Targets (#22 → achieved), Mandatory Test Matrix, Change Control.
- `docs/REFACTORING_EXECUTION_PLAN.md`: append entry with verified baseline, level-by-level measurement, fixes applied, plugin rationale.
- `docs/REPO_MAP.md`: note Psalm scope now covers `routes/` and uses the Laravel plugin.
- `docs/AI_REPO_MAP.md`: add Psalm-Laravel plugin to static-analysis toolchain surface.

### Slice 5 — Quality gate

- Run the canonical sequence strictly in order, one command at a time:
  1. `composer run lint`
  2. `composer run analyse` (PHPStan L10 + Psalm — the gate now exercises the new plugin configuration)
  3. `php artisan test`
  4. `npm run lint`
  5. `npm run lint:ox`
  6. `npm run format:ox:check`
  7. `npm run type-check`
  8. `npm run test`
  9. `npm run build`
- Scope note: `composer.json` changed (new dev dependency) → `composer.lock` is part of the diff. Route/config unchanged → no `optimize:clear`/`route:list` step required.

## Definition of done

1. `psalm/plugin-laravel:^3.0` installed, registered in `psalm.xml`, scope extended to `routes/`.
2. `errorLevel="4"` green by source-typing, no baseline file.
3. `PsalmLadderScopeParityGuardrailTest` locks the configuration.
4. `docs/ARCHITECTURE_REFACTOR_NEXT.md` records `Q3` as Closed with convergence impact and exit-target update; `docs/REFACTORING_EXECUTION_PLAN.md` records the executed work, measurements, and checks.
5. The quality gate is green and the executed checks are reported.
