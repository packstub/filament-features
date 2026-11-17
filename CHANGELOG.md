# Changelog

All notable changes to `packstub/filament-features` are documented here.

## Unreleased

- Filament Features is now free and open source under the MIT licence, and installs from Packagist with `composer require packstub/filament-features`
- **Upgrading:** if your project installs it from the `packstub.dev` Composer repository, remove that repository and its `http-basic.packstub.dev` credentials, then run `composer update packstub/filament-features`. Nothing changes in the code.
- Docs: a shorter Features list in the README and on the docs index, and the README lists the `features:list` and `features:prune` commands
- `composer.json`'s homepage points at the package's page on packstub.dev

## 1.1.0 — 2026-09-03

- Choose who may manage flags and overrides with `FeaturesPlugin::canManageUsing()` or a `manageFeatures` Gate ability
- Tenant members can no longer flip global kill switches: on a tenant panel the admin pages are hidden and return 403 unless allowed
- Run several panels with different scopes and plans: settings now apply per panel, and unset ones fall back to the config file
- **Upgrading:** on a tenant panel, let your operators in with `canManageUsing()` or the Gate ability. Seeders and tests that switch panels with `Filament::setCurrentPanel()` call `FeaturesPlugin::get()->configure()` afterwards.
- Docs: screenshots of the flags and overrides pages re-taken from an operator panel

## 1.0.0 — 2026-08-26

First release of Filament Features: plans, limits and overrides per tenant, with feature gating in your Filament panel.

- Features and plans in config or on the plugin, as on/off flags or limits
- Overrides per tenant, with an expiry and a reason, and global kill switches
- Check a feature anywhere: `Features::canUse()`, the `feature:` middleware, `@feature` in Blade, `requiresFeature()` on actions
- A Plan page for tenants, and Feature flags and Overrides pages for operators
- Events when a feature is overridden or a limit is exceeded

Docs: https://packstub.dev/docs/filament-features
