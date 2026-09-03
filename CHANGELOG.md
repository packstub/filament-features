# Changelog

All notable changes to `packstub/filament-features` are documented here.

## Unreleased

### Changed

- `FeaturesPlugin` applies its settings per panel: `configure()` pushes the panel's config overrides, feature and plan definitions, usage counters, plan resolver and upgrade URL into the runtime when the panel is registered *and* again when it boots, so an app can run several panels with different scope models and plans. Settings a panel leaves unset now fall back to the config file instead of what another panel applied. Seeders and tests that switch panels with `Filament::setCurrentPanel()` call `FeaturesPlugin::get()->configure()` afterwards.
- `Features::resetDefinitions()` forgets runtime definitions so they reload from config.

## 1.0.0 — 2026-08-26

Initial release.

### Added

- Feature and plan definitions (config or `FeaturesPlugin::features()` / `plans()`), boolean and limit types, groups, units, descriptions.
- Resolution: scope override → plan → global override → default; `Features::value/active/limit/used/remaining/canUse/assertCanUse/snapshot/source`, `Features::for($scope)`, `HasFeatures` trait.
- Overrides with expiry and reason, global kill switches, `features:prune`, per-request memo and optional cache store, `FeatureOverridden` / `FeatureOverrideCleared` / `FeatureLimitExceeded` events.
- Gating: `feature:` middleware (abort or redirect), `@feature` Blade directive, `requiresFeature()` macro on actions and schema components, `RequiresFeature` trait, `<x-packstub-features::locked>` component.
- Filament pages: tenant `Plan`, admin `FeatureFlags` and `FeatureOverrides`; `features:list` command.
