<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use App\Repositories\AccountOrderReadRepository;
use App\Repositories\AdminOrderReadRepository;
use App\Repositories\AdminProductReadRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\PromotionRepository;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;
use Tests\TestCase;

class RepositoryBusinessDecisionBoundaryTest extends TestCase
{
    public function test_read_repositories_do_not_depend_on_authorization_or_transition_boundaries(): void
    {
        $repositoryPaths = [
            app_path('Repositories/AccountOrderReadRepository.php'),
            app_path('Repositories/AdminOrderReadRepository.php'),
            app_path('Repositories/AdminProductReadRepository.php'),
            app_path('Repositories/CategoryRepository.php'),
            app_path('Repositories/PromotionRepository.php'),
        ];

        $forbiddenPatterns = [
            'Gate::',
            '->can(',
            'authorize(',
            'TransitionPolicy',
            'WebhookProcessingOutcome',
            'DomainException',
            'AuthenticationException',
            'hasRole(',
            'RoleName::',
        ];

        foreach ($repositoryPaths as $repositoryPath) {
            $contents = File::get($repositoryPath);

            foreach ($forbiddenPatterns as $pattern) {
                $this->assertStringNotContainsString(
                    $pattern,
                    $contents,
                    basename($repositoryPath)." must stay a data-access boundary and not depend on {$pattern}."
                );
            }
        }
    }

    public function test_read_repositories_do_not_return_boolean_business_decisions(): void
    {
        $repositories = [
            AccountOrderReadRepository::class,
            AdminOrderReadRepository::class,
            AdminProductReadRepository::class,
            CategoryRepository::class,
            PromotionRepository::class,
        ];

        foreach ($repositories as $repositoryClass) {
            foreach ((new \ReflectionClass($repositoryClass))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $repositoryClass) {
                    continue;
                }

                $returnType = $method->getReturnType();

                if ($returnType instanceof ReflectionNamedType) {
                    $this->assertFalse(
                        $returnType->isBuiltin() && $returnType->getName() === 'bool',
                        "{$repositoryClass}::{$method->getName()}() must return a data structure, not a boolean business decision."
                    );

                    continue;
                }

                if (! $returnType instanceof ReflectionUnionType) {
                    continue;
                }

                foreach ($returnType->getTypes() as $unionType) {
                    $this->assertFalse(
                        $unionType instanceof ReflectionNamedType
                            && $unionType->isBuiltin()
                            && $unionType->getName() === 'bool',
                        "{$repositoryClass}::{$method->getName()}() must not expose boolean business decisions through union return types."
                    );
                }
            }
        }
    }
}
