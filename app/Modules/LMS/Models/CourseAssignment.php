<?php

declare(strict_types=1);

namespace App\Modules\LMS\Models;

use App\Modules\Employee\Models\User;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CourseAssignment extends Model
{
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'course_assignments';

    protected $fillable = [
        'tenant_id', 'course_id', 'user_id', 'assigned_by',
        'due_date', 'completed_at', 'status',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'completed'
            && $this->due_date !== null
            && $this->due_date->isPast();
    }
}
