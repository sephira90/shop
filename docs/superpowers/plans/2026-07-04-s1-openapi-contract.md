# S1 — OpenAPI Contract Source Of Truth

**Status:** Active — execution starts after this plan is approved.
**Scope owner:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` (item 13 in Execution Queue, P1).
**Source finding:** `docs/ARCHITECTURE_REFACTOR_NEXT.md` → Promoted Quality/Reliability Blocks → S1.
**Sequence constraint:** after `R1` (closed; `error.code` taxonomy must be in the spec); before `C0` (module API freeze references the spec).

## Resolved design decisions (`2026-07-04`)

- **OpenAPI version: 3.0.x** (not 3.1). Rationale: `cebe/php-openapi` 1.8.0 (the chosen stable validator) supports OpenAPI 3.0.x only; 3.1 support sits in an unmerged PR (milestone 2.0.0) since 2021. The `/api/v1` surface does not exercise any 3.1-specific feature (no webhooks, no JSON Schema dialect override, nullable fields modeled via `x-nullable` or `oneOf`+`null`). The roadmap wording "OpenAPI 3.1" is updated to "OpenAPI 3.0" in the closed-block definition.
- **Tooling: `cebe/php-openapi` + custom trait.** `cebe/php-openapi: ^1.8.0` as dev-dependency for spec parsing/validation. A custom `AssertsOpenApiResponse` trait wraps the matching logic in a `assertMatchesOpenApiSpec($response, $method, $path)` helper. No new runtime services, no middleware.
- **First-increment scope: auth + catalog + cart** (all three groups together per user choice). ~14 endpoints total.
- **Test strategy: trait + assert helper** integrated into existing feature tests. Spec is parsed once per test run and cached in a static property.
- **Spec location: `docs/api/openapi.yaml`** (single file; `components/schemas` reuse via `$ref`).

## Verified baseline (`2026-07-04`, mapping done by explore agent)

- 14 endpoints in scope (8 auth, 3 catalog, 3 cart). All controller file:line, response DTO `toArray()` shapes, status codes, and FormRequest rules tabulated in agent transcript.
- **Three envelope shapes only**: `{data}`, `{data,meta}`, `{error}`.
- **No JsonResource classes** for these domains — bodies come from `*ResultDto::toArray()` per ADR-0002.
- **Two distinct error shapes** that must be modeled separately:
  - **Shape A** (`ErrorResponseController`) — controller-caught `AuthApplicationException` on auth endpoints: `{error:{message, request_id?, type}}` (no `code`, no `validation`).
  - **Shape B** (`ErrorResponseRenderer`) — `ApiExceptionRenderer`-emitted on everything else: `{error:{message, request_id?, type, code, validation?}}` (`validation` only on 422). `code` ∈ closed 9-member `ApiErrorCode` enum.
- **Pagination meta** is a 4-field shape (`current_page`, `last_page`, `per_page`, `total`) — trimmed from Laravel default; no `links`/`from`/`to`.
- **Notable spec deltas vs. naive assumptions** (must reflect in spec):
  1. Catalog paths: `/catalog/products`, `/catalog/products/{slug}`, `/catalog/categories` — no public `/products` root.
  2. `GET /auth/email/verify/{id}/{hash}` is signed GET, not POST.
  3. `/auth/me` exists; `/auth/user` does not.
  4. `POST /auth/email/verification-notification` is POST.
  5. Cart: no PATCH/PUT (upsert via `POST /cart/items`), no `DELETE /cart` clear-all.
  6. `DELETE /cart/items/{variantId}` returns 200 + cart snapshot (not 204, not `ApiResponse::deleted()`).
  7. `POST /auth/register` is the only 201 in this surface.
  8. Login "invalid credentials" is 422 (credential folding), not 401.
  9. Cart endpoints support guest identity via `guest_token` query param OR `X-Cart-Token` header (no `auth:sanctum`).
  10. `X-Correlation-Id` request header is optional everywhere; echoed as `error.request_id` on error responses only.

## Invariants to lock

1. **Spec is source of truth for `/api/v1` shape.** Response divergence fails the relevant feature test; the test helper does not silently widen acceptance.
2. **Envelope contract preserved.** `{data}`, `{data,meta}`, `{error}` are the only top-level shapes; the spec does not invent new envelopes.
3. **Error taxonomy fixed.** The 9 `ApiErrorCode` literals are the only allowed `error.code` values; the spec enumerates them. `error.type` (PHP class basename) is documented as free-form string for backward compatibility (deprecated-but-stable).
4. **No runtime contract changes.** S1 only formalizes the existing contract; it does not change controllers, DTOs, or middleware.
5. **Spec lint in CI.** A dedicated test parses `docs/api/openapi.yaml` with `cebe/php-openapi` and fails on structural errors.

## Out of scope (deliberate)

- Frontend contract-type generation (TypeScript types from spec) — separate follow-up decision per module (roadmap item 3 of S1 is plan-only here).
- Admin routes (`/api/v1/admin/*`) — covered in a later block (admin is not in the "auth + catalog + cart" scope agreed for S1's first increment).
- Account orders / checkout / webhook endpoints — later blocks.
- OpenAPI 3.1 migration — deferred until a stable PHP validator exists (risk register follow-up).
- Coverage floor enforcement for the spec — out of DoD.

## Implementation slices

### Slice 1 — Spec skeleton + envelope/error schemas

- Create `docs/api/openapi.yaml` with:
  - `openapi: 3.0.3`, `info` (title, version, description), `servers` (single local entry).
  - `components/schemas`:
    - `PaginationMeta` (4 integer fields).
    - `ErrorResponseController` (Shape A): `error.{message, request_id?, type}`.
    - `ErrorResponseRenderer` (Shape B): `error.{message, request_id?, type, code, validation?}`.
    - `ApiErrorCode` enum (9 string literals).
    - Reusable `AuthUser`, `AuthToken`, `CatalogProduct`, `CatalogProductVariant`, `CatalogCategory`, `CartItem`, `CartSummary`, `Cart` component schemas built verbatim from DTO `toArray()` outputs.
- **Smoke:** `vendor/bin/php-openapi validate docs/api/openapi.yaml` → exit 0.

### Slice 2 — Auth operation definitions

- Add 8 `paths` entries under `/auth`:
  - `POST /auth/register` (201 + body, 422 validation, 4xx handler error Shape A)
  - `POST /auth/login` (200, 422 invalid credentials Shape A, 422 validation Shape B, 429)
  - `POST /auth/logout` (200 message body, 401 renderer)
  - `GET /auth/me` (200, 401 renderer)
  - `PATCH /auth/profile` (200, 401, 422)
  - `POST /auth/forgot-password` (200 message, 422 Shape A broker, 422 validation)
  - `POST /auth/reset-password` (200 message, 422 Shape A broker, 422 validation)
  - `GET /auth/email/verify/{id}/{hash}` (200 message, 403 invalid signature, 404 user not found Shape A, 403 hash mismatch Shape A)
  - `POST /auth/email/verification-notification` (200 message, 401 renderer)
- Document `throttle:auth.login` and `throttle:6,1` limits via `x-throttle` vendor extension (informational).
- **Smoke:** spec validates.

### Slice 3 — Catalog operation definitions

- Add 3 `paths` entries under `/catalog`:
  - `GET /catalog/products` (200 paginated `{data:[...], meta:PaginationMeta}`, 422 validation). Query params from `CatalogIndexRequest` rules.
  - `GET /catalog/products/{slug}` (200 single product, 404 renderer `not_found`).
  - `GET /catalog/categories` (200 `{data:[CatalogCategory]}`).
- **Smoke:** spec validates.

### Slice 4 — Cart operation definitions

- Add 3 `paths` entries under `/cart`:
  - `GET /cart` (200 cart snapshot, 401 renderer, 403 policy)
  - `POST /cart/items` (200 cart snapshot, 422 validation, 403 policy)
  - `DELETE /cart/items/{variantId}` (200 cart snapshot, 422 validation, 403 policy)
- Document optional `guest_token` query param and `X-Cart-Token` header on all three.
- **Smoke:** spec validates.

### Slice 5 — Validation infrastructure (composer dep + helper trait)

- Add `cebe/php-openapi: ^1.8.0` to `require-dev`.
- Create `tests/Support/OpenApi/SpecAssertionHelper.php` (or trait):
  - Loads `docs/api/openapi.yaml` once per test run via `Spec::fromFile()`, caches in static property.
  - `assertResponseMatchesOpenApiSpec(TestResponse $response, string $method, string $path): void` — locates the operation by method+path, validates status code presence and response body against the declared schema, with a clear failure message naming the diverging field.
- **Smoke:** a trivial smoke test (e.g., asserting the spec loads) passes.

### Slice 6 — Feature test conformance coverage

- Wire `assertResponseMatchesOpenApiSpec` into the existing auth/catalog/cart feature tests for the success path of each in-scope endpoint. (Do not rewrite existing assertions — add the spec conformance assertion alongside.)
- Add a dedicated `OpenApiSpecStructureTest` (unit) that:
  - Validates `docs/api/openapi.yaml` parses via `cebe/php-openapi` with no structural errors.
  - Asserts every declared path has at least one response.
  - Asserts every operation declares the canonical error responses where applicable (401 for auth-required, 422 for validated, etc.).
- **Smoke:** `php artisan test --filter="OpenApiSpecStructureTest"` green; existing feature tests still green with the added conformance assertions.

### Slice 7 — Guardrail + docs sync

- New `tests/Unit/Architecture/OpenApiContractSourceGuardrailTest.php` asserts:
  - `docs/api/openapi.yaml` exists and declares `openapi: 3.0.x`.
  - `composer.json` requires `cebe/php-openapi` in `require-dev`.
  - `tests/Support/OpenApi/SpecAssertionHelper.php` exists.
  - The `ApiErrorCode` literals in `app/Support/Api/ApiErrorCode.php` match the `enum` declared in the spec's `ApiErrorCode` component (closed-set parity — no drift).
  - The spec covers the 14 in-scope paths (no missing path).
- `docs/ARCHITECTURE_REFACTOR_NEXT.md`: mark `S1` Closed, advance next block to Active (C0 or whichever is next), append Closed-block definition, update Risk Register (close #8, optional #24 OpenAPI 3.1 follow-up), Exit Targets (#24 achieved), Change Control. Update roadmap wording from "OpenAPI 3.1" to "OpenAPI 3.0".
- `docs/REFACTORING_EXECUTION_PLAN.md`: append entry with verified baseline, slice-by-slice decisions, and the quality gate result.

### Slice 8 — Quality gate

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
- Scope note: backend changed (composer.json, tests/, docs/) → all backend steps run; frontend untouched → frontend steps run for regression safety.

## Definition of done

1. `docs/api/openapi.yaml` covers all 14 in-scope endpoints with verbatim-from-DTO response schemas and the two error shapes.
2. `cebe/php-openapi` validates the spec structurally; the structure test fails on any divergence.
3. Each in-scope endpoint has at least one feature test asserting response/spec conformance via `assertResponseMatchesOpenApiSpec`.
4. `OpenApiContractSourceGuardrailTest` locks spec existence, version, tooling presence, and `ApiErrorCode` parity.
5. `docs/ARCHITECTURE_REFACTOR_NEXT.md` records `S1` as Closed; `docs/REFACTORING_EXECUTION_PLAN.md` records the executed work and checks.
6. The quality gate is green and the executed checks are reported.
7. No runtime contract changes — controllers, DTOs, middleware untouched.
