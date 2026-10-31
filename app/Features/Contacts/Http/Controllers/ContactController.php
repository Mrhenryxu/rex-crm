<?php

namespace App\Features\Contacts\Http\Controllers;

use App\Features\Contacts\Actions\DeleteContact;
use App\Features\Contacts\Actions\GetContactDetails;
use App\Features\Contacts\Actions\SearchContacts;
use App\Features\Contacts\Actions\UpsertContact;
use App\Features\Contacts\Http\Requests\SearchContactsRequest;
use App\Features\Contacts\Http\Requests\UpsertContactRequest;
use App\Features\Contacts\Http\Resources\ContactResource;
use App\Features\Contacts\Models\Contact;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ContactController extends Controller
{
    public function index(SearchContactsRequest $request, SearchContacts $searchContacts): AnonymousResourceCollection
    {
        return ContactResource::collection(
            $searchContacts->handle($request->toCriteria())->withQueryString(),
        );
    }

    /**
     * Upsert keyed on the normalised email: 201 when created, 200 when an existing contact was updated.
     */
    public function store(UpsertContactRequest $request, UpsertContact $upsertContact): JsonResponse
    {
        $result = $upsertContact->handle($request->toData());

        $response = ContactResource::make($result->contact)->response();

        return $result->created
            ? $response->setStatusCode(201)->header('Location', route('contacts.show', $result->contact))
            : $response;
    }

    public function show(string $contact, GetContactDetails $getContactDetails): ContactResource
    {
        return ContactResource::make($getContactDetails->handle($contact));
    }

    public function update(UpsertContactRequest $request, Contact $contact, UpsertContact $upsertContact): ContactResource
    {
        return ContactResource::make($upsertContact->handle($request->toData(), $contact)->contact);
    }

    public function destroy(Contact $contact, DeleteContact $deleteContact): Response
    {
        $deleteContact->handle($contact);

        return response()->noContent();
    }
}
