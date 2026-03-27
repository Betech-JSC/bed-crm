<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralConversion extends Model
{
    protected $fillable = [
        'referral_code_id', 'referred_name', 'referred_email', 'referred_phone',
        'status', 'deal_value', 'commission_amount',
    ];

    public function referralCode(): BelongsTo { return $this->belongsTo(ReferralCode::class); }
}
