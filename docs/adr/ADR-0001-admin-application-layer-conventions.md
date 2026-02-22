# ADR-0001: Admin Application-Layer Conventions and DI Boundaries

- Status: Accepted
- Date: 2026-02-22
- Owners: Backend Core

## Context

Admin API controllers historically mixed transport concerns with orchestration and directly depended on repositories/services.
This increased coupling, made controller changes riskier, and complicated architecture scaling across modules.

We already migrated `Orders`, `Promotions`, `Products`, and `Categories` to the application-layer handler model.
The pattern now needs to be explicitly fixed as an architectural contract.

## Decision

For admin modules under `App\Http\Controllers\Api\V1\Admin\*Controller`:

1. Controllers are `thin transport` adapters only.
2. Controller constructor dependencies are application-layer handlers only (`App\Application\Admin\...\*Handler`).
3. Controllers do not directly depend on `App\Repositories\*` or `App\Services\*`.
4. Read flows are represented by `Query + QueryHandler`.
5. Write flows are represented by `Command + CommandHandler`.
6. Authorization remains in controllers/requests (transport boundary), business orchestration runs in handlers.
7. API contract remains unchanged (`ApiResponse` + Resources), regardless of orchestration refactors.

## Consequences

### Positive

- Lower coupling between HTTP layer and domain/data layers.
- Consistent extension model for new admin modules.
- Easier architectural review and regression detection.
- Predictable DI boundaries for tests and future extraction.

### Trade-offs

- More classes per feature flow (command/query + handler).
- Requires discipline in naming and folder conventions.

## Conventions

- Queries:
  - `App\Application\Admin\<Module>\Queries\<Action>Query`
  - `App\Application\Admin\<Module>\Queries\<Action>Handler`
- Commands:
  - `App\Application\Admin\<Module>\Commands\<Action>Command`
  - `App\Application\Admin\<Module>\Commands\<Action>Handler`
- Controller method format:
  - authorize
  - construct command/query DTO
  - call handler
  - return resource/envelope

## Guardrails

- Architecture test `tests/Unit/Architecture/AdminControllerArchitectureTest.php` enforces constructor dependency boundaries:
  - dependency namespace starts with `App\Application\Admin\`
  - dependency class ends with `Handler`

## Rollback Strategy

If a critical production issue appears, rollback is safe at commit level because this ADR does not alter API payload contracts; it only constrains internal orchestration structure.
