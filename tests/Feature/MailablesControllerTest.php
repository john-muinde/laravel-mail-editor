<?php

use Illuminate\Support\Facades\Config;

test('page loads in allowed environments', function () {
    Config::set('maileclipse.allowed_environments', ['staging', 'testing', 'local']);

    $this->get('maileclipse/mailables')
        ->assertOk();
});

test('returns error when viewing in prohibited env by default', function () {
    Config::set('maileclipse.allowed_environments', ['staging', 'local']);

    $this->get('maileclipse/mailables')
        ->assertStatus(403);
});
