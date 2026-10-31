<?php

namespace App\Features\Contacts\Models;

use App\Features\Contacts\Support\EmailAddress;
use Database\Factories\ContactFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Contact extends Model
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, HasUuids;

    public const int LATEST_CALLS_LIMIT = 10;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
    ];

    protected static function newFactory(): ContactFactory
    {
        return ContactFactory::new();
    }

    /**
     * @return HasMany<ContactCall, $this>
     */
    public function calls(): HasMany
    {
        return $this->hasMany(ContactCall::class);
    }

    /**
     * The most recent calls, newest first. Eager loading applies the limit per contact.
     *
     * @return HasMany<ContactCall, $this>
     */
    public function latestCalls(): HasMany
    {
        return $this->calls()
            ->latest()
            ->orderByDesc('id')
            ->limit(self::LATEST_CALLS_LIMIT);
    }

    /**
     * Always store the normalised (trimmed, lower-cased) email.
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (?string $value): ?string => EmailAddress::normalise($value));
    }
}
