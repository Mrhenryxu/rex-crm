<?php

namespace App\Features\Contacts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceContactCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'string', 'in:sync,async'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mode.in' => 'The mode must be sync or async.',
        ];
    }

    public function isAsync(): bool
    {
        return $this->validated('mode', 'async') !== 'sync';
    }
}
