<?php

namespace App\Features\Contacts\Models;

use App\Features\Contacts\Enums\CallOutcome;
use App\Features\Contacts\Enums\CallStatus;
use Database\Factories\ContactCallFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $contact_id
 * @property string $phone
 * @property CallStatus $status
 * @property CallOutcome|null $outcome
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ContactCall extends Model
{
    /** @use HasFactory<ContactCallFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'phone',
        'status',
        'outcome',
        'completed_at',
    ];

    protected static function newFactory(): ContactCallFactory
    {
        return ContactCallFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CallStatus::class,
            'outcome' => CallOutcome::class,
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function isPending(): bool
    {
        return $this->status === CallStatus::Pending;
    }
}
