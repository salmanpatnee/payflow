<?php

test('registration is disabled', function () {
    // Registration has been disabled in fortify.php
    // Attempting to register should fail
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // Should get 404 since the route doesn't exist
    $response->assertNotFound();
});
