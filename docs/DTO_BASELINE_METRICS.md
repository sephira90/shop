# DTO Baseline Metrics

Baseline snapshot captured before DTO migration waves.

- Captured at: `2026-02-24`
- Scope:
  - `app/Application` payload arrays and handler return arrays
  - `resources/js/api` + `resources/js/mappers` `unknown` usage

## Metrics

1. `public array $payload|$filters` in `app/Application`: `14`
2. `function handle(...): array` in `app/Application`: `8`
3. `unknown` tokens in `resources/js/api|mappers`: `59`

## Commands used

```powershell
rg -n "public array \$(payload|filters)" app/Application
rg -n "function handle\(.*\): array" app/Application
rg -n "\bunknown\b" resources/js/api resources/js/mappers
```
