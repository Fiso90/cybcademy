<?php

declare(strict_types=1);

namespace App\Modules\LMS\Models;

use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class QuestionBankItem extends Model
{
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'question_bank';

    protected $fillable = ['tenant_id', 'question_text', 'question_type', 'options', 'correct_answer'];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
    ];
}
