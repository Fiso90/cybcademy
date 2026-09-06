<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use App\Modules\Employee\Models\User;
use App\Modules\Organisation\Models\Department;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HumanRiskScore extends Model
{
    use HasUuids;
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'human_risk_scores';

    protected $fillable = ['tenant_id', 'user_id', 'department_id', 'score', 'calculated_at', 'contributing_factors', 'created_at'];

    protected $casts = [
        'score' => 'decimal:2',
        'calculated_at' => 'datetime',
        'contributing_factors' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
