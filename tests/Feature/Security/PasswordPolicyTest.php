<?php

test('registration rejects weak password', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'Sps@2026#Secure',
    ]);

    $response->assertSessionHasErrors('password');
});

test('registration rejects password without uppercase', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'sps@2026#secure',
        'password_confirmation' => 'sps@2026#secure',
    ]);

    $response->assertSessionHasErrors('password');
});

test('registration rejects password without symbols', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Sps20260Secure',
        'password_confirmation' => 'Sps20260Secure',
    ]);

    $response->assertSessionHasErrors('password');
});

test('registration accepts strong password', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'newuser@test.com',
        'password' => 'Sps@2026#Secure',
        'password_confirmation' => 'Sps@2026#Secure',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});
