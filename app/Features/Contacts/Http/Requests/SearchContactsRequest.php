<?php

namespace App\Features\Contacts\Http\Requests;

use App\Features\Contacts\Data\ContactSearchCriteria;
use App\Features\Contacts\Rules\ContactRules;
use Illuminate\Foundation\Http\FormRequest;

class SearchContactsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ContactRules::search();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ContactRules::searchMessages();
    }

    public function toCriteria(): ContactSearchCriteria
    {
        return ContactSearchCriteria::fromValidated($this->validated());
    }
}
