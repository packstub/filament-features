# Packstub Features

**Plans, limits and flags — per tenant.**

Packstub Features is a premium plugin that turns "what is this tenant allowed to do?" into one call. Define features and plans in code, resolve per tenant with overrides for trials and exceptions, check limits against real usage, and gate pages, actions, routes and Blade with the same vocabulary. Ships with a tenant-facing "Your plan" page and admin pages for flags and overrides.

## Features

- **[Features and plans in code](installation.md#defining-features-and-plans)**: on/off features and numeric limits, bundled into plans; the plan comes from any source.
- **[One call to check](checking.md#values)**: whether a feature is on, its limit and what's left against real usage.
- **[Gating everywhere](checking.md#gating)**: routes, actions, form fields, pages and Blade, with an upgrade card when something is locked.
- **[Overrides](overrides.md)**: trials, beta access and custom limits per tenant, global kill switches, each with an expiry and a reason.
- **[Plan and admin pages](checking.md#the-plan-page)**: "Your plan" with usage bars for tenants, flags and overrides for operators.
- **[Teams and Tenancy](configuration.md#with-filament-teams)**: seat limits from the plan, and Filament Tenancy's workspaces as the scope.
- **[Caching, commands and events](overrides.md#caching)**: a per-request memo with an optional cache store, list and prune commands, events when a limit is hit.

## Guides

| Guide | What's inside |
|---|---|
| [Installation](installation.md) | Registry auth, definitions, tenant model, admin pages |
| [Checking features](checking.md) | Values, limits, usage, enforcing, gating, the plan page |
| [Overrides and flags](overrides.md) | Per-tenant overrides, kill switches, admin pages, caching, events |
| [Configuration](configuration.md) | Every key, the plugin API, commands, Teams and Tenancy |
