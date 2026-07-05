<?php

declare(strict_types=1);

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Application\Dto\ForgotAuthPasswordInputDto;
use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if user can perform this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }

    /**
     * Build typed DTO for forgot-password flow.
     */
    public function toDto(): ForgotAuthPasswordInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return ForgotAuthPasswordInputDto::fromValidated($validated);
    }
}
