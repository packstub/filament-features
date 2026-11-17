# packstub/filament-features

Free (MIT) Filament v5 plugin: feature flags and entitlements per tenant (plans, limits, overrides, gating). Installed from Packagist; one name everywhere, **Filament Features** (just **Features** on filamentphp.com, whose directory drops "Filament").

## Commands

```bash
composer test               # Pest
composer lint               # Pint
bash .github/scripts/package-health.sh   # what the Package health workflow checks
```

## Layout

- `src/Features.php` — the resolver (scope override → plan → global → default) and override store; `ScopedFeatures` is the per-scope fluent API.
- `src/Support/Feature|Plan` — definitions; `src/Filament/Pages` — `Plan` (tenant), `FeatureFlags` + `FeatureOverrides` (admin).
- Gating lives in the service provider (middleware alias `feature`, `@feature`, `requiresFeature` macros).
- `docs/` customer docs synced to packstub.dev.

## Conventions

- Every change needs a test and a `CHANGELOG.md` line. A version's section becomes the top of its GitHub release (the workflow appends the merged pull requests), so keep it short and plain: an optional lead sentence, at most ~6 one-line bullets saying what a user gets, method names only when needed to use them; always keep breaking changes (an `**Upgrading:**` line), security fixes, new minimum versions and migrations to run.
- Limit features: `null` = unlimited, `0` = off. Never store resolved values — only overrides.
