<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Application\Auth\Dto\ForgotAuthPasswordInputDto;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
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
