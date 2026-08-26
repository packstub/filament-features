# Changelog

All notable changes to `packstub/filament-features` are documented here.

## 1.0.0 — 2026-08-26

Initial release.

### Added

- Feature and plan definitions (config or `FeaturesPlugin::features()` / `plans()`), boolean and limit types, groups, units, descriptions.
- Resolution: scope override → plan → global override → default; `Features::value/active/limit/used/remaining/canUse/assertCanUse/snapshot/source`, `Features::for($scope)`, `HasFeatures` trait.
- Overrides with expiry and reason, global kill switches, `features:prune`, per-request memo and optional cache store, `FeatureOverridden` / `FeatureOverrideCleared` / `FeatureLimitExceeded` events.
- Gating: `feature:` middleware (abort or redirect), `@feature` Blade directive, `requiresFeature()` macro on actions and schema components, `RequiresFeature` trait, `<x-packstub-features::locked>` component.
- Filament pages: tenant `Plan`, admin `FeatureFlags` and `FeatureOverrides`; `features:list` command.
