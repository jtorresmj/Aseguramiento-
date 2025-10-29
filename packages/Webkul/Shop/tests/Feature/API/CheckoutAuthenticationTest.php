<?php

use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

/**
 * Test: Checkout endpoints require authentication
 */
it('requires authentication to access checkout summary', function () {
    // Act - Try to access checkout summary without token
    $response = getJson(route('shop.checkout.onepage.summary'));

    // Assert - Should return 401 Unauthorized
    $response->assertUnauthorized();
});

it('allows authenticated customer to view checkout summary', function () {
    // Arrange - Create customer and token
    $customer = Customer::factory()->create([
        'status'      => 1,
        'is_verified' => 1,
    ]);
    $token = $customer->createToken('test-token')->plainTextToken;

    // Act - Access checkout summary with token
    $response = getJson(route('shop.checkout.onepage.summary'), [
        'Authorization' => 'Bearer '.$token,
    ]);

    // Assert - Not 401 Unauthorized (authentication works, even if no cart exists)
    expect($response->status())->not()->toBe(401);
});

/**
 * Test: Storing address requires authentication
 */
it('requires authentication to store checkout address', function () {
    // Act - Try to store address without token
    $response = postJson(route('shop.checkout.onepage.addresses.store'), [
        'billing' => [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
            'address'    => '123 Main St',
            'country'    => 'US',
            'state'      => 'CA',
            'city'       => 'Los Angeles',
            'postcode'   => '90001',
            'phone'      => '1234567890',
        ],
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Storing shipping method requires authentication
 */
it('requires authentication to store shipping method', function () {
    // Act - Try to store shipping method without token
    $response = postJson(route('shop.checkout.onepage.shipping_methods.store'), [
        'shipping_method' => 'free_free',
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Storing payment method requires authentication
 */
it('requires authentication to store payment method', function () {
    // Act - Try to store payment method without token
    $response = postJson(route('shop.checkout.onepage.payment_methods.store'), [
        'payment' => [
            'method' => 'cashondelivery',
        ],
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Creating order requires authentication
 */
it('requires authentication to create order', function () {
    // Act - Try to create order without token
    $response = postJson(route('shop.checkout.onepage.orders.store'));

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Authenticated customer can access checkout endpoints with valid token
 */
it('allows authenticated customer to store shipping method', function () {
    // Arrange - Create customer with token and cart
    $customer = Customer::factory()->create([
        'status'      => 1,
        'is_verified' => 1,
    ]);
    $token = $customer->createToken('test-token')->plainTextToken;

    // Act as customer
    $this->actingAs($customer, 'customer');

    // Create a simple product and add to cart
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    // Get the actual product model from the collection
    $productModel = $product->first();
    Cart::addProduct($productModel, ['quantity' => 1]);

    // Add billing address to cart
    Cart::saveAddresses([
        'billing' => [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => $customer->email,
            'address'    => ['123 Main St'],
            'country'    => 'US',
            'state'      => 'CA',
            'city'       => 'Los Angeles',
            'postcode'   => '90001',
            'phone'      => '1234567890',
        ],
        'shipping' => [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => $customer->email,
            'address'    => ['123 Main St'],
            'country'    => 'US',
            'state'      => 'CA',
            'city'       => 'Los Angeles',
            'postcode'   => '90001',
            'phone'      => '1234567890',
        ],
    ]);

    // Act - Store shipping method with authentication
    $response = postJson(
        route('shop.checkout.onepage.shipping_methods.store'),
        [
            'shipping_method' => 'free_free',
        ],
        [
            'Authorization' => 'Bearer '.$token,
        ]
    );

    // Assert - Should succeed or return specific business logic error
    // (not 401 Unauthorized)
    expect($response->status())->not()->toBe(401);
});

/**
 * Test: Invalid token is rejected for checkout operations
 */
it('rejects invalid token for checkout operations', function () {
    // Act - Try to access checkout with invalid token
    $response = getJson(route('shop.checkout.onepage.summary'), [
        'Authorization' => 'Bearer invalid-token-abc123',
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Expired or revoked tokens are rejected
 */
it('rejects revoked token for checkout operations', function () {
    // Arrange - Create customer and token
    $customer = Customer::factory()->create([
        'status'      => 1,
        'is_verified' => 1,
    ]);

    $tokenObject = $customer->createToken('test-token');
    $token = $tokenObject->plainTextToken;

    // Revoke the token
    $tokenObject->accessToken->delete();

    // Act - Try to access with revoked token
    $response = getJson(route('shop.checkout.onepage.summary'), [
        'Authorization' => 'Bearer '.$token,
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Multiple concurrent sessions with different tokens
 */
it('allows customer to have multiple active tokens', function () {
    // Arrange - Create customer with two tokens
    $customer = Customer::factory()->create([
        'status'      => 1,
        'is_verified' => 1,
    ]);

    $token1 = $customer->createToken('mobile-app')->plainTextToken;
    $token2 = $customer->createToken('web-app')->plainTextToken;

    // Act - Access with first token
    $response1 = getJson(route('shop.checkout.onepage.summary'), [
        'Authorization' => 'Bearer '.$token1,
    ]);

    // Act - Access with second token
    $response2 = getJson(route('shop.checkout.onepage.summary'), [
        'Authorization' => 'Bearer '.$token2,
    ]);

    // Assert - Both tokens should work (not return 401 Unauthorized)
    expect($response1->status())->not()->toBe(401);
    expect($response2->status())->not()->toBe(401);
});

/**
 * Test: Token can be used across different checkout endpoints
 */
it('allows same token to be used across all checkout endpoints', function () {
    // Arrange
    $customer = Customer::factory()->create([
        'status'      => 1,
        'is_verified' => 1,
    ]);
    $token = $customer->createToken('test-token')->plainTextToken;

    $headers = ['Authorization' => 'Bearer '.$token];

    // Act & Assert - Summary endpoint
    $response1 = getJson(route('shop.checkout.onepage.summary'), $headers);
    expect($response1->status())->not()->toBe(401);

    // Act & Assert - Shipping methods endpoint
    $response2 = postJson(
        route('shop.checkout.onepage.shipping_methods.store'),
        ['shipping_method' => 'free_free'],
        $headers
    );
    expect($response2->status())->not()->toBe(401);

    // Act & Assert - Payment methods endpoint
    $response3 = postJson(
        route('shop.checkout.onepage.payment_methods.store'),
        ['payment' => ['method' => 'cashondelivery']],
        $headers
    );
    expect($response3->status())->not()->toBe(401);
});
