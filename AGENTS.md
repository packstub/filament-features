# packstub/filament-features

Paid Filament v5 plugin: feature flags and entitlements per tenant (plans, limits, overrides, gating). Paid: **Filament Features** on packstub.dev, **Packstub Features** on filamentphp.com.

## Commands

```bash
composer test               # Pest
composer lint               # Pint
```

## Layout

- `src/Features.php` — the resolver (scope override → plan → global → default) and override store; `ScopedFeatures` is the per-scope fluent API.
- `src/Support/Feature|Plan` — definitions; `src/Filament/Pages` — `Plan` (tenant), `FeatureFlags` + `FeatureOverrides` (admin).
- Gating lives in the service provider (middleware alias `feature`, `@feature`, `requiresFeature` macros).
- `docs/` customer docs.

## Conventions

- Every change needs a test and a `CHANGELOG.md` line.
- Limit features: `null` = unlimited, `0` = off. Never store resolved values — only overrides.
