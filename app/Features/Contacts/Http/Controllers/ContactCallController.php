<?php

namespace App\Features\Contacts\Http\Controllers;

use App\Features\Contacts\Actions\PlaceContactCall;
use App\Features\Contacts\Http\Requests\PlaceContactCallRequest;
use App\Features\Contacts\Http\Resources\ContactCallResource;
use App\Features\Contacts\Models\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class ContactCallController extends Controller
{
    /**
     * Async (default): 202 with the pending call. Sync: 201 with the completed call.
     */
    public function store(PlaceContactCallRequest $request, Contact $contact, PlaceContactCall $placeContactCall): JsonResponse
    {
        $async = $request->isAsync();

        return ContactCallResource::make($placeContactCall->handle($contact, $async))
            ->response()
            ->setStatusCode($async ? 202 : 201);
    }
}
