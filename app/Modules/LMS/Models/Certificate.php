<?php

declare(strict_types=1);

namespace App\Modules\LMS\Models;

use App\Modules\Employee\Models\User;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * `certificate_uid` backs the public verification endpoint
 * (Phase 8 Section 7) and is intentionally globally unique, not
 * tenant-scoped for uniqueness purposes - see migration docblock.
 */
final class Certificate extends Model
{
    use HasUuids;
    use BelongsToTenant;

    public $timestamps = false; // created_at set explicitly below; no updates expected

    protected $table = 'certificates';

    protected $fillable = ['tenant_id', 'user_id', 'course_id', 'certificate_uid', 'issued_at', 'file_path'];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
