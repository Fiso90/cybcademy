<?php

declare(strict_types=1);

namespace App\Modules\PhishingSimulation\Models;

use App\Modules\Employee\Models\User;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PhishingCampaign extends Model
{
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'phishing_campaigns';

    protected $fillable = ['tenant_id', 'name', 'template_id', 'target_scope', 'scheduled_at', 'status', 'created_by'];

    protected $casts = [
        'target_scope' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PhishingTemplate::class, 'template_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(PhishingResult::class, 'campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
