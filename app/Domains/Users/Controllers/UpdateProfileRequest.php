<?php

declare(strict_types=1);

namespace App\Domains\Users\Controllers;

use App\Domains\Users\Application\Dto\UpdateAuthProfileInputDto;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProfileRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * Build typed DTO for profile update flow.
     */
    public function toDto(): UpdateAuthProfileInputDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return UpdateAuthProfileInputDto::fromValidated($validated);
    }
}
