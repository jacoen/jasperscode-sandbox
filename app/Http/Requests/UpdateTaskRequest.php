<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'project' => ['missing'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(config('definitions.statuses'))],
            'image' => ['nullable'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'description' => strip_tags($this->description),
        ]);
    }
}
