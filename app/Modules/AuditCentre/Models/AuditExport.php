<?php

declare(strict_types=1);

namespace App\Modules\AuditCentre\Models;

use App\Modules\Employee\Models\User;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuditExport extends Model
{
    use HasUuids;
    use BelongsToTenant;

    protected $table = 'audit_exports';

    protected $fillable = [
        'tenant_id', 'requested_by', 'status', 'filters',
        'file_path', 'record_count', 'failure_reason', 'completed_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'completed_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
