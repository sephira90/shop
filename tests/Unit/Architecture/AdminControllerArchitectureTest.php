<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\PromotionController;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class AdminControllerArchitectureTest extends TestCase
{
    /**
     * Ensure admin controllers depend only on application-layer handlers.
     */
    public function test_admin_controllers_depend_on_application_handlers_only(): void
    {
        $controllers = [
            OrderController::class,
            PromotionController::class,
            ProductController::class,
            CategoryController::class,
        ];

        foreach ($controllers as $controllerClass) {
            $reflection = new ReflectionClass($controllerClass);
            $constructor = $reflection->getConstructor();

            $this->assertNotNull($constructor, "{$controllerClass} must define constructor dependencies.");

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                $this->assertInstanceOf(
                    ReflectionNamedType::class,
                    $type,
                    "{$controllerClass} constructor parameter {$parameter->getName()} must have a named type."
                );
                $this->assertFalse(
                    $type->isBuiltin(),
                    "{$controllerClass} constructor parameter {$parameter->getName()} must be a class type."
                );

                $dependency = $type->getName();

                $this->assertStringStartsWith(
                    'App\\Application\\Admin\\',
                    $dependency,
                    "{$controllerClass} must not depend on repository/service layer directly."
                );
                $this->assertStringEndsWith(
                    'Handler',
                    $dependency,
                    "{$controllerClass} constructor dependencies must be handler classes."
                );
            }
        }
    }
}
