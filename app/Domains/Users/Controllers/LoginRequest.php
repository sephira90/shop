<?php

declare(strict_types=1);

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Application\Dto\LoginAuthInputDto;
use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:8', 'max:128'],
            'device_name' => ['nullable', 'string', 'max:80'],
            'guest_token' => ['nullable', 'string', 'max:80'],
        ];
    }

    /**
     * Build typed DTO for login flow.
     */
    public function toDto(): LoginAuthInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return LoginAuthInputDto::fromValidated($validated);
    }
}
