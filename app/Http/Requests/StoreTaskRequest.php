<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'min:3'],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['image'],
            'author_id' => ['required', 'exists:users,id'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'description' => strip_tags($this->description),
            'author_id' => auth()->id(),
        ]);
    }

    public function messages(): array
    {
        return [
            'attachments.*.image' => 'The attachments may only contain images.',
        ];
    }
}
