<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SMTPSetting extends Model
{

    protected $table = 'smtp_settings';

    protected $fillable = [
        'account_id',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
    ];

    protected $hidden = [
        'password',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get mail configuration array for Laravel Mail
     */
    public function toMailConfig(): array
    {
        return [
            'transport' => 'smtp',
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => null,
            'auth_mode' => null,
        ];
    }
}
