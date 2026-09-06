<?php

declare(strict_types=1);

namespace App\Modules\LMS\Models;

use App\Modules\Employee\Models\User;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AssessmentAttempt extends Model
{
    use HasUuids;
    use BelongsToTenant;

    protected $table = 'assessment_attempts';

    protected $fillable = ['tenant_id', 'assessment_id', 'user_id', 'score', 'passed', 'answers', 'submitted_at'];

    protected $casts = [
        'answers' => 'array',
        'passed' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
