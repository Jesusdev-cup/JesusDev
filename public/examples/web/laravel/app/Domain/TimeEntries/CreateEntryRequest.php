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

    public function rules(): array
    {
        return [
            'task' => 'required|string|max:255',
            'minutes' => 'required|integer|min:1',
        ];
    }
}
