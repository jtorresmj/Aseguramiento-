<?php

use Webkul\Customer\Models\Customer;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/**
 * Test: Customer can login and receive Sanctum token
 */
it('allows customer to login and returns sanctum token', function () {
    // Arrange - Create a customer
    $customer = Customer::factory()->create([
        'email'       => 'test@example.com',
        'password'    => bcrypt('password123'),
        'status'      => 1,
        'is_verified' => 1,
    ]);

    // Act - Login
    $response = postJson(route('shop.api.customers.session.create'), [
        'email'    => 'test@example.com',
        'password' => 'password123',
    ]);

    // Assert - Check response structure
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'customer' => [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                ],
                'token',
                'token_type',
            ],
            'message',
        ]);

    // Assert - Token type is Bearer
    expect($response->json('data.token_type'))->toBe('Bearer');

    // Assert - Token is not empty
    expect($response->json('data.token'))->not()->toBeEmpty();

    // Assert - Customer data matches
    expect($response->json('data.customer.email'))->toBe('test@example.com');
});

/**
 * Test: Login fails with invalid credentials
 */
it('returns error when login credentials are invalid', function () {
    // Arrange
    Customer::factory()->create([
        'email'       => 'test@example.com',
        'password'    => bcrypt('password123'),
        'status'      => 1,
        'is_verified' => 1,
    ]);

    // Act - Login with wrong password
    $response = postJson(route('shop.api.customers.session.create'), [
        'email'    => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    // Assert
    $response->assertForbidden()
        ->assertJsonStructure(['message']);
});

/**
 * Test: Login fails when customer is not activated
 */
it('returns error when customer account is not activated', function () {
    // Arrange - Create inactive customer
    Customer::factory()->create([
        'email'       => 'inactive@example.com',
        'password'    => bcrypt('password123'),
        'status'      => 0, // Inactive
        'is_verified' => 1,
    ]);

    // Act
    $response = postJson(route('shop.api.customers.session.create'), [
        'email'    => 'inactive@example.com',
        'password' => 'password123',
    ]);

    // Assert
    $response->assertForbidden()
        ->assertJsonStructure(['message']);
});

/**
 * Test: Login fails when customer is not verified
 */
it('returns error when customer email is not verified', function () {
    // Arrange - Create unverified customer
    Customer::factory()->create([
        'email'       => 'unverified@example.com',
        'password'    => bcrypt('password123'),
        'status'      => 1,
        'is_verified' => 0, // Not verified
    ]);

    // Act
    $response = postJson(route('shop.api.customers.session.create'), [
        'email'    => 'unverified@example.com',
        'password' => 'password123',
    ]);

    // Assert
    $response->assertForbidden()
        ->assertJsonStructure(['message']);
});

/**
 * Test: Authenticated customer can access their profile
 */
it('allows authenticated customer to access their profile with token', function () {
    // Arrange - Create and login customer
    $customer = Customer::factory()->create([
        'email'       => 'test@example.com',
        'password'    => bcrypt('password123'),
        'status'      => 1,
        'is_verified' => 1,
    ]);

    // Create token
    $token = $customer->createToken('test-token')->plainTextToken;

    // Act - Access profile with Bearer token
    $response = getJson(route('shop.api.customers.me'), [
        'Authorization' => 'Bearer '.$token,
    ]);

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'customer' => [
                    'id',
                    'email',
                    'first_name',
                    'last_name',
                ],
            ],
        ]);

    expect($response->json('data.customer.email'))->toBe('test@example.com');
});

/**
 * Test: Unauthenticated request to profile endpoint fails
 */
it('denies access to profile without authentication token', function () {
    // Act - Try to access profile without token
    $response = getJson(route('shop.api.customers.me'));

    // Assert - Should return 401 Unauthorized
    $response->assertUnauthorized();
});

/**
 * Test: Invalid token is rejected
 */
it('rejects invalid authentication token', function () {
    // Act - Try to access with invalid token
    $response = getJson(route('shop.api.customers.me'), [
        'Authorization' => 'Bearer invalid-token-12345',
    ]);

    // Assert
    $response->assertUnauthorized();
});
