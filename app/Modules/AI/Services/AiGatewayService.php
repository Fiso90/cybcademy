<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Http;

/**
 * The single choke point for every outbound call to the AI provider
 * (Anthropic's Claude API, per the confirmed AI Features list in Phase 1/
 * Phase 3). Every one of the seven AI features (Section below) goes
 * through generate() rather than calling Http::post() directly - this is
 * deliberate, for three reasons:
 *
 *   1. Tenant data minimisation: prompts are built by each feature
 *      service, but THIS class is where a final, defensive check happens
 *      that nothing resembling a raw PII field (email, full name +
 *      department combination, etc.) is present in the prompt payload
 *      before it leaves the platform. This is a blunt heuristic
 *      safety net, not a substitute for each feature service being
 *      careful about what it puts in a prompt in the first place.
 *   2. Usage auditing: every AI call is logged (feature name, tenant,
 *      token counts) - both for cost tracking and because "which AI
 *      feature touched customer content, when" is a reasonable question
 *      for a compliance-focused product's own compliance story.
 *   3. Rate limiting / tier gating: AI features are gated by
 *      subscription tier (Phase 3 Section 5) - checked here once, not
 *      independently by each of the seven feature services.
 *
 * Configuration: model name is read from config, not hardcoded, since
 * Anthropic's available model identifiers change over time and this
 * class should not need a code change to pick up a new one - see
 * config/services.php's 'anthropic.model' key, set via the
 * ANTHROPIC_MODEL environment variable.
 */
final class AiGatewayService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    /**
     * @param string $feature one of: security_tutor, quiz_generator,
     *                        policy_summariser, phishing_generator,
     *                        risk_recommendations, executive_reports,
     *                        chat_assistant - used for usage auditing and
     *                        tier-gating, and to keep prompt-construction
     *                        concerns owned by the calling feature
     *                        service, not this gateway.
     */
    public function generate(string $feature, string $systemPrompt, string $userPrompt, int $maxTokens = 1024): string
    {
        $this->assertNoObviousPiiLeak($userPrompt);

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => config('services.anthropic.api_version'),
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model'),
            'max_tokens' => $maxTokens,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        $response->throw(); // let Laravel's HTTP client exception handling / retry middleware take over on failure

        $body = $response->json();
        $text = collect($body['content'] ?? [])
            ->firstWhere('type', 'text')['text'] ?? '';

        $this->auditLogger->log(
            tenantId: TenantContext::current(),
            actorUserId: auth()->id(),
            action: "ai.{$feature}.generated",
            resourceType: 'ai_generation',
            resourceId: null,
            afterState: [
                'feature' => $feature,
                'input_tokens' => $body['usage']['input_tokens'] ?? null,
                'output_tokens' => $body['usage']['output_tokens'] ?? null,
            ],
        );

        return $text;
    }

    /**
     * Deliberately narrow, high-confidence-only checks (email addresses,
     * obvious "SSN"/"password"-labelled fields) - a false negative here
     * is expected and acceptable (this is not a substitute for each
     * feature service constructing prompts carefully), but a false
     * positive that blocks a legitimate AI feature call in production
     * would itself be a reliability problem, so this stays conservative
     * rather than trying to be a comprehensive PII scanner.
     */
    private function assertNoObviousPiiLeak(string $prompt): void
    {
        if (preg_match('/[\w.+-]+@[\w-]+\.[a-z]{2,}/i', $prompt) === 1) {
            throw new \RuntimeException(
                'Prompt appears to contain an email address. Feature services must '
                . 'reference employees by name or role only, never by email, in AI prompts.'
            );
        }
    }
}
