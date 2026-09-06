<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Modules\Policy\Models\Policy;
use Illuminate\Support\Facades\Storage;

/**
 * FR-9.3: AI Policy Summariser. Deliberately returns the summary as a
 * plain string to the caller rather than writing it back onto the Policy
 * model - a policy document's authoritative text is the uploaded file
 * itself (Epic E3); an AI-generated plain-language summary is a reading
 * aid displayed alongside it, never a replacement for it, and never
 * persisted as if it were part of the legally-relevant policy record.
 * If a tenant wants the summary cached, that's a UI-layer concern
 * (cache the string in Redis keyed by policy_id + version, not stored in
 * the policies table itself).
 */
final class PolicySummariserService
{
    public function __construct(
        private readonly AiGatewayService $ai,
    ) {
    }

    public function summarise(Policy $policy): string
    {
        // Extracting text from the uploaded PDF/DOCX (Storage::get +
        // a text-extraction step) is a Phase 13 tooling detail; this
        // excerpt assumes plain text is already available via the
        // storage layer for brevity.
        $documentText = Storage::disk('private')->get($policy->file_path);

        $systemPrompt = 'You summarise corporate security policies in plain, '
            . 'non-legal language for employees with no compliance background. '
            . 'Keep the summary under 150 words. Do not invent obligations not '
            . 'present in the source text.';

        return $this->ai->generate(
            'policy_summariser',
            $systemPrompt,
            "Summarise this policy titled \"{$policy->title}\":\n\n{$documentText}",
            maxTokens: 400,
        );
    }
}
