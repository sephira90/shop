<?php

declare(strict_types=1);

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Application\Dto\ResetAuthPasswordInputDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', Password::default(), 'max:128', 'confirmed'],
        ];
    }

    /**
     * Build typed DTO for reset-password flow.
     */
    public function toDto(): ResetAuthPasswordInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return ResetAuthPasswordInputDto::fromValidated($validated);
    }
}
