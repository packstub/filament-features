<?php

use Illuminate\Support\Facades\Hash;
use Packstub\Features\Tests\Fixtures\Team;
use Packstub\Features\Tests\Fixtures\User;
use Packstub\Features\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

function createUser(): User
{
    static $n = 0;
    $n++;

    return User::create(['name' => "User {$n}", 'email' => "user{$n}@example.com", 'password' => Hash::make('secret')]);
}

function createTeam(string $name = 'Acme', ?string $plan = null): Team
{
    static $n = 0;
    $n++;

    return Team::create(['name' => $name, 'slug' => strtolower($name).'-'.$n, 'plan' => $plan]);
}
