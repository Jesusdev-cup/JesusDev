<?php

namespace App\Domain\TimeEntries;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request = the contract: authorize() is the permission matrix,
 * rules() is the schema. Validation errors return 422 automatically
 * in Laravel's error shape — the contract's error form.
 */
class CreateEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('time.write');
    }

    /**
     * Trim input before validation — the contract normalizes, then validates.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['task' => trim((string) $this->input('task', ''))]);
    }

    public function rules(): array
    {
        return [
            'task' => 'bail|required|string|max:255',
            'minutes' => 'bail|required|integer|min:1|max:1440',
        ];
    }
}
