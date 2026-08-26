# packstub/filament-features

Paid Filament v5 plugin: feature flags and entitlements per tenant (plans, limits, overrides, gating). Sold on packstub.dev as **Filament Features**; listed on filamentphp.com as **Packstub Features**.

## Commands

```bash
composer test               # Pest
composer lint               # Pint
```

## Layout

- `src/Features.php` — the resolver (scope override → plan → global → default) and override store; `ScopedFeatures` is the per-scope fluent API.
- `src/Support/Feature|Plan` — definitions; `src/Filament/Pages` — `Plan` (tenant), `FeatureFlags` + `FeatureOverrides` (admin).
- Gating lives in the service provider (middleware alias `feature`, `@feature`, `requiresFeature` macros).
- `docs/` customer docs (synced to the store). Demo: `../../demos/packstub-demo` (with Tenancy + Teams) and `../../demos/filament-teams-demo` (browser E2E, screenshots rig `scripts/screenshots-features.mjs`).

## Conventions

- PHP 8.3+, Pint, Pest; every change needs a test. Keep `CHANGELOG.md` current.
- Limit features: `null` = unlimited, `0` = off. Never store resolved values — only overrides.
