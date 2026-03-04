<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Policies\CartPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CouponPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PromotionPolicy;
use Illuminate\Support\Facades\Gate;
use ReflectionNamedType;
use Tests\TestCase;

class PolicyCompletenessMatrixGuardrailTest extends TestCase
{
    /**
     * @return array<class-string, array{policy: class-string, actions: list<string>}>
     */
    private function expectedPolicyMatrix(): array
    {
        return [
            Cart::class => [
                'policy' => CartPolicy::class,
                'actions' => ['viewAny', 'modify'],
            ],
            Category::class => [
                'policy' => CategoryPolicy::class,
                'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
            ],
            Coupon::class => [
                'policy' => CouponPolicy::class,
                'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
            ],
            Order::class => [
                'policy' => OrderPolicy::class,
                'actions' => ['viewAny', 'view', 'update'],
            ],
            Product::class => [
                'policy' => ProductPolicy::class,
                'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
            ],
            Promotion::class => [
                'policy' => PromotionPolicy::class,
                'actions' => ['viewAny', 'view', 'create', 'update', 'delete'],
            ],
        ];
    }

    public function test_route_bound_models_have_registered_policy_mappings(): void
    {
        foreach ($this->expectedPolicyMatrix() as $modelClass => $expectation) {
            $policy = Gate::getPolicyFor($modelClass);

            $this->assertNotNull($policy, "{$modelClass} must resolve to a registered policy.");
            $this->assertInstanceOf(
                $expectation['policy'],
                $policy,
                "{$modelClass} must stay mapped to {$expectation['policy']}."
            );
        }
    }

    public function test_registered_policies_cover_actions_used_by_routes_and_requests(): void
    {
        foreach ($this->expectedPolicyMatrix() as $modelClass => $expectation) {
            $reflectionClass = new \ReflectionClass($expectation['policy']);

            foreach ($expectation['actions'] as $action) {
                $this->assertTrue(
                    $reflectionClass->hasMethod($action),
                    "{$expectation['policy']} must implement {$action}() for {$modelClass}."
                );

                $method = $reflectionClass->getMethod($action);

                $this->assertSame(
                    $expectation['policy'],
                    $method->getDeclaringClass()->getName(),
                    "{$expectation['policy']} must declare {$action}() itself."
                );

                $returnType = $method->getReturnType();

                $this->assertInstanceOf(
                    ReflectionNamedType::class,
                    $returnType,
                    "{$expectation['policy']}::{$action}() must declare a bool return type."
                );

                $this->assertTrue(
                    $returnType->isBuiltin() && $returnType->getName() === 'bool',
                    "{$expectation['policy']}::{$action}() must return bool."
                );

                $this->assertGreaterThanOrEqual(
                    1,
                    $method->getNumberOfParameters(),
                    "{$expectation['policy']}::{$action}() must accept a User argument."
                );
            }
        }
    }
}
