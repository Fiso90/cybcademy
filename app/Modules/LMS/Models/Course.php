<?php

declare(strict_types=1);

namespace App\Modules\LMS\Models;

use App\Modules\Employee\Models\User;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @see /mnt/user-data/outputs/CybCademy_Phase5_Database_Design.md Section 3.5
 */
final class Course extends Model
{
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'courses';

    protected $fillable = ['tenant_id', 'title', 'description', 'status', 'created_by', 'is_onboarding_default'];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('sequence_order');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
