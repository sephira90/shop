<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Application\Auth\Dto\RegisterAuthInputDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class RegisterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', Password::default(), 'max:128', 'confirmed'],
        ];
    }

    /**
     * Build typed DTO for register flow.
     */
    public function toDto(): RegisterAuthInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return RegisterAuthInputDto::fromValidated($validated);
    }
}
