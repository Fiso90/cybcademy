<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Models;

use App\Modules\Employee\Models\User;
use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Notification extends Model
{
    use HasUuids;
    use BelongsToTenant;

    public $timestamps = false;

    protected $table = 'notifications';

    protected $fillable = ['tenant_id', 'user_id', 'type', 'payload', 'read_at', 'created_at'];

    protected $casts = [
        'payload' => 'array',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
