<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CheckoutController;
use ReflectionClass;
use ReflectionNamedType;
use Tests\TestCase;

class PublicApiControllerArchitectureTest extends TestCase
{
    /**
     * Ensure public API controllers depend only on application-layer handlers.
     */
    public function test_public_api_controllers_depend_on_application_handlers_only(): void
    {
        $controllers = [
            AuthController::class,
            CartController::class,
            CatalogController::class,
            CheckoutController::class,
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
                    'App\\Application\\',
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
