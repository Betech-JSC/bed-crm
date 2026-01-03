<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'name',
        'description',
        'type',
        'filters',
        'contacts_count',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_active' => 'boolean',
        'contacts_count' => 'integer',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function listContacts(): HasMany
    {
        return $this->hasMany(EmailListContact::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class);
    }

    /**
     * Update contacts count
     */
    public function updateContactsCount(): void
    {
        $this->contacts_count = $this->listContacts()
            ->whereNull('unsubscribed_at')
            ->count();
        $this->save();
    }
}
