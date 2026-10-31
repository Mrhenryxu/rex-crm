<?php

namespace App\Features\Contacts\Http\Requests;

use App\Features\Contacts\Data\ContactData;
use App\Features\Contacts\Rules\ContactRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Body for POST /contacts (upsert by email) and PUT /contacts/{contact} (full replacement).
 */
class UpsertContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authentication and authorisation are out of scope for this module.
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ContactRules::contact();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ContactRules::contactMessages();
    }

    public function toData(): ContactData
    {
        return ContactData::fromValidated($this->validated());
    }
}
