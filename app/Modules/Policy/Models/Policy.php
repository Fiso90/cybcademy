<?php

declare(strict_types=1);

namespace App\Modules\Policy\Models;

use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Policy extends Model
{
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'policies';

    protected $fillable = ['tenant_id', 'title', 'version', 'file_path', 'published_at'];

    protected $casts = ['published_at' => 'datetime'];

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(PolicyAcknowledgement::class);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
