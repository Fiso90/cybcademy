<?php

declare(strict_types=1);

namespace App\Modules\LMS\Models;

use App\Support\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Assessment extends Model
{
    use HasUuids;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'assessments';

    protected $fillable = ['tenant_id', 'course_id', 'title', 'passing_score'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(QuestionBankItem::class, 'assessment_questions', 'assessment_id', 'question_id')
            ->withPivot('sequence_order')
            ->orderBy('assessment_questions.sequence_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }
}
