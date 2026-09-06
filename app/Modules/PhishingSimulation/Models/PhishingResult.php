<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Models;

use App\Modules\Employee\Models\User;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PhishingResult extends Model
{
    use HasUuids;
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'phishing_results';

    protected $fillable = ['tenant_id', 'campaign_id', 'user_id', 'event_type', 'event_at', 'created_at'];

    protected $casts = ['event_at' => 'datetime'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PhishingCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
