<?php

use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * Test: Cart endpoints require authentication
 */
it('requires authentication to access cart index', function () {
    // Act - Try to access cart without token
    $response = getJson(route('shop.api.checkout.cart.index'));

    // Assert - Should return 401 Unauthorized
    $response->assertUnauthorized();
});

it('allows authenticated customer to view their cart', function () {
    // Arrange - Create customer and token
    $customer = Customer::factory()->create([
        'status'      => 1,
        'is_verified' => 1,
    ]);
    $token = $customer->createToken('test-token')->plainTextToken;

    // Act - Access cart with token
    $response = getJson(route('shop.api.checkout.cart.index'), [
        'Authorization' => 'Bearer '.$token,
    ]);

    // Assert
    $response->assertOk()
        ->assertJsonStructure(['data']);
});

/**
 * Test: Adding products to cart requires authentication
 */
it('requires authentication to add product to cart', function () {
    // Arrange - Create a product
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    // Act - Try to add to cart without token
    $response = postJson(route('shop.api.checkout.cart.store'), [
        'product_id' => $product->id,
        'quantity'   => 1,
    ]);

    // Assert
    $response->assertUnauthorized();
});

it('allows authenticated customer to add product to cart', function () {
    // Arrange - Create customer, token, and product
    $customer = Customer::factory()->create([
        'status'      => 1,
        'is_verified' => 1,
    ]);
    $token = $customer->createToken('test-token')->plainTextToken;

    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    // Act - Add product with authentication
    $response = postJson(
        route('shop.api.checkout.cart.store'),
        [
            'product_id' => $product->id,
            'quantity'   => 1,
        ],
        [
            'Authorization' => 'Bearer '.$token,
        ]
    );

    // Assert
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'items',
            ],
            'message',
        ]);
});

/**
 * Test: Updating cart requires authentication
 */
it('requires authentication to update cart', function () {
    // Act - Try to update cart without token
    $response = putJson(route('shop.api.checkout.cart.update'), [
        'qty' => [
            1 => 2,
        ],
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Removing items from cart requires authentication
 */
it('requires authentication to remove items from cart', function () {
    // Act - Try to delete from cart without token
    $response = deleteJson(route('shop.api.checkout.cart.destroy'), [
        'cart_item_id' => 1,
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Applying coupon requires authentication
 */
it('requires authentication to apply coupon', function () {
    // Act - Try to apply coupon without token
    $response = postJson(route('shop.api.checkout.cart.coupon.apply'), [
        'code' => 'TESTCOUPON',
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Removing coupon requires authentication
 */
it('requires authentication to remove coupon', function () {
    // Act - Try to remove coupon without token
    $response = deleteJson(route('shop.api.checkout.cart.coupon.remove'));

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Estimating shipping requires authentication
 */
it('requires authentication to estimate shipping methods', function () {
    // Act - Try to estimate shipping without token
    $response = postJson(route('shop.api.checkout.cart.estimate_shipping'), [
        'country'  => 'US',
        'state'    => 'CA',
        'postcode' => '90001',
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Cross-sell products require authentication
 */
it('requires authentication to view cross-sell products', function () {
    // Act - Try to get cross-sell products without token
    $response = getJson(route('shop.api.checkout.cart.cross-sell.index'));

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Moving to wishlist requires authentication
 */
it('requires authentication to move items to wishlist', function () {
    // Act - Try to move to wishlist without token
    $response = postJson(route('shop.api.checkout.cart.move_to_wishlist'), [
        'ids' => [1],
        'qty' => [1],
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Removing selected items requires authentication
 */
it('requires authentication to remove selected items from cart', function () {
    // Act - Try to remove selected items without token
    $response = deleteJson(route('shop.api.checkout.cart.destroy_selected'), [
        'ids' => [1, 2],
    ]);

    // Assert
    $response->assertUnauthorized();
});

/**
 * Test: Invalid token is rejected for cart operations
 */
it('rejects invalid token for cart operations', function () {
    // Arrange
    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    // Act - Try with invalid token
    $response = postJson(
        route('shop.api.checkout.cart.store'),
        [
            'product_id' => $product->id,
            'quantity'   => 1,
        ],
        [
            'Authorization' => 'Bearer invalid-token-xyz',
        ]
    );

    // Assert
    $response->assertUnauthorized();
});
