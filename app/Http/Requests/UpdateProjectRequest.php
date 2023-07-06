<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'min:3', 'max:255'],
            'due_date' => ['required', 'date', 'after:today', 'before:2030-12-31'],
            'status' => ['required', 'string', Rule::in(config('definitions.statuses'))],
            'is_pinned' => ['boolean'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'description' => strip_tags($this->description),
            'is_pinned' => $this->toBoolean($this->is_pinned),
        ]);
    }

    private function toBoolean($booleable)
    {
        return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
