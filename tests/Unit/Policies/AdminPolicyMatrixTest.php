<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Role;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\CouponPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromotionPolicy;
use Tests\TestCase;

class AdminPolicyMatrixTest extends TestCase
{
    /**
     * Ensure product policy gates access by role.
     */
    public function test_product_policy_role_matrix(): void
    {
        $policy = new ProductPolicy;
        $product = new Product;

        $admin = $this->makeUserWithRoles(1, RoleName::ADMIN);
        $manager = $this->makeUserWithRoles(2, RoleName::MANAGER);
        $customer = $this->makeUserWithRoles(3, RoleName::CUSTOMER);

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->viewAny($manager));
        $this->assertFalse($policy->viewAny($customer));

        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->create($manager));
        $this->assertFalse($policy->create($customer));

        $this->assertTrue($policy->view($admin, $product));
        $this->assertTrue($policy->view($manager, $product));
        $this->assertFalse($policy->view($customer, $product));

        $this->assertTrue($policy->update($admin, $product));
        $this->assertTrue($policy->update($manager, $product));
        $this->assertFalse($policy->update($customer, $product));

        $this->assertTrue($policy->delete($admin, $product));
        $this->assertFalse($policy->delete($manager, $product));
        $this->assertFalse($policy->delete($customer, $product));
    }

    /**
     * Ensure category policy gates access by role.
     */
    public function test_category_policy_role_matrix(): void
    {
        $policy = new CategoryPolicy;
        $category = new Category;

        $admin = $this->makeUserWithRoles(1, RoleName::ADMIN);
        $manager = $this->makeUserWithRoles(2, RoleName::MANAGER);
        $customer = $this->makeUserWithRoles(3, RoleName::CUSTOMER);

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->viewAny($manager));
        $this->assertFalse($policy->viewAny($customer));

        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->create($manager));
        $this->assertFalse($policy->create($customer));

        $this->assertTrue($policy->view($admin, $category));
        $this->assertTrue($policy->view($manager, $category));
        $this->assertFalse($policy->view($customer, $category));

        $this->assertTrue($policy->update($admin, $category));
        $this->assertTrue($policy->update($manager, $category));
        $this->assertFalse($policy->update($customer, $category));

        $this->assertTrue($policy->delete($admin, $category));
        $this->assertFalse($policy->delete($manager, $category));
        $this->assertFalse($policy->delete($customer, $category));
    }

    /**
     * Ensure promotion and coupon policies allow only admin/manager update flows.
     */
    public function test_promotion_and_coupon_policy_role_matrix(): void
    {
        $promotionPolicy = new PromotionPolicy;
        $couponPolicy = new CouponPolicy;
        $promotion = new Promotion;
        $coupon = new Coupon;

        $admin = $this->makeUserWithRoles(1, RoleName::ADMIN);
        $manager = $this->makeUserWithRoles(2, RoleName::MANAGER);
        $customer = $this->makeUserWithRoles(3, RoleName::CUSTOMER);

        $this->assertTrue($promotionPolicy->viewAny($admin));
        $this->assertTrue($promotionPolicy->viewAny($manager));
        $this->assertFalse($promotionPolicy->viewAny($customer));

        $this->assertTrue($promotionPolicy->view($admin, $promotion));
        $this->assertTrue($promotionPolicy->view($manager, $promotion));
        $this->assertFalse($promotionPolicy->view($customer, $promotion));

        $this->assertTrue($promotionPolicy->create($admin));
        $this->assertTrue($promotionPolicy->create($manager));
        $this->assertFalse($promotionPolicy->create($customer));

        $this->assertTrue($promotionPolicy->update($admin, $promotion));
        $this->assertTrue($promotionPolicy->update($manager, $promotion));
        $this->assertFalse($promotionPolicy->update($customer, $promotion));

        $this->assertTrue($promotionPolicy->delete($admin, $promotion));
        $this->assertTrue($promotionPolicy->delete($manager, $promotion));
        $this->assertFalse($promotionPolicy->delete($customer, $promotion));

        $this->assertTrue($couponPolicy->update($admin, $coupon));
        $this->assertTrue($couponPolicy->update($manager, $coupon));
        $this->assertFalse($couponPolicy->update($customer, $coupon));
    }

    /**
     * Ensure order policy allows admin/manager and owner read access.
     */
    public function test_order_policy_role_and_ownership_matrix(): void
    {
        $policy = new OrderPolicy;
        $admin = $this->makeUserWithRoles(1, RoleName::ADMIN);
        $manager = $this->makeUserWithRoles(2, RoleName::MANAGER);
        $owner = $this->makeUserWithRoles(3, RoleName::CUSTOMER);
        $otherCustomer = $this->makeUserWithRoles(4, RoleName::CUSTOMER);

        $order = new Order;
        $order->user_id = $owner->id;

        $this->assertTrue($policy->viewAny($admin));
        $this->assertTrue($policy->viewAny($manager));
        $this->assertFalse($policy->viewAny($owner));

        $this->assertTrue($policy->view($admin, $order));
        $this->assertTrue($policy->view($manager, $order));
        $this->assertTrue($policy->view($owner, $order));
        $this->assertFalse($policy->view($otherCustomer, $order));

        $this->assertTrue($policy->update($admin, $order));
        $this->assertTrue($policy->update($manager, $order));
        $this->assertFalse($policy->update($owner, $order));
    }

    /**
     * Build in-memory user with attached role relation for policy checks.
     */
    private function makeUserWithRoles(int $id, RoleName ...$roles): User
    {
        $user = new User;
        $user->id = $id;
        $user->setRelation(
            'roles',
            collect($roles)->map(static fn (RoleName $role): Role => new Role(['name' => $role->value])),
        );

        return $user;
    }
}
