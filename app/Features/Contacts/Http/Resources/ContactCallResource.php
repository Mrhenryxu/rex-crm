<?php

namespace App\Features\Contacts\Http\Resources;

use App\Features\Contacts\Models\ContactCall;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContactCall
 */
class ContactCallResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contact_id' => $this->contact_id,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'outcome' => $this->outcome?->value,
            'completed_at' => $this->completed_at?->toIso8601ZuluString(),
            'created_at' => $this->created_at->toIso8601ZuluString(),
        ];
    }
}
