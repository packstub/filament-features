<?php

return [
    'plan' => [
        'title' => 'Your plan',
        'subheading' => "You're on the :plan plan.",
        'upgrade' => 'Upgrade',
        'default_group' => 'Features',
        'usage' => ':used of :limit used',
        'usage_unlimited' => ':used used',
        'custom' => 'custom',
    ],
    'flags' => [
        'title' => 'Feature flags',
        'subheading' => 'Defaults and plan values come from code; a global override applies to every scope without its own override.',
        'global' => 'Global override',
        'enable_globally' => 'Enable globally',
        'disable_globally' => 'Disable globally',
        'disable_confirm' => 'Turns the feature off everywhere, except for scopes with their own override.',
        'clear' => 'Clear',
        'until' => 'until :time',
    ],
    'overrides' => [
        'title' => 'Feature overrides',
        'empty' => 'No overrides yet',
        'create' => 'New override',
        'edit' => 'Edit',
        'remove' => 'Remove',
    ],
    'fields' => [
        'scope' => 'Scope',
        'feature' => 'Feature',
        'value' => 'Value',
        'default' => 'Default',
        'enabled' => 'Enabled',
        'limit' => 'Limit',
        'limit_help' => 'Leave empty for unlimited.',
        'reason' => 'Reason',
        'expires' => 'Expires',
    ],
    'values' => [
        'included' => 'Included',
        'not_included' => 'Not included',
        'unlimited' => 'Unlimited',
        'never' => 'Never',
    ],
    'locked' => [
        'title' => ':feature is not included in your plan',
        'body' => 'Upgrade to unlock it.',
    ],
    'notifications' => [
        'saved' => 'Saved',
        'removed' => 'Override removed',
    ],
    'errors' => [
        'inactive' => 'The :feature feature is not available on your plan.',
        'limit_reached' => "You've reached the :feature limit of :limit.",
    ],
];
