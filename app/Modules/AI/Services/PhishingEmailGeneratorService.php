<?php

declare(strict_types=1);

namespace App\Modules\AI\Services;

use App\Modules\PhishingSimulation\Models\PhishingTemplate;
use App\Support\TenantContext;

/**
 * FR-9.4: AI Phishing Email Generator. Access-restricted per Phase 1's
 * explicit note ("internal simulation use only, access-restricted") -
 * enforced at the request/controller layer (only Security Officer/
 * Trainer roles can reach this, same gate as
 * StorePhishingCampaignRequest from Epic E4), not by anything in this
 * service itself, since a service class is the wrong layer to encode an
 * authorization decision (Phase 4 Section 4's layering).
 *
 * Generated templates are tenant-scoped (not shared platform templates -
 * see PhishingTemplate's nullable tenant_id design from Epic E4), since
 * an AI-generated lure is likely tailored to the requesting tenant's
 * industry/context and should not leak into another tenant's simulation
 * library.
 */
final class PhishingEmailGeneratorService
{
    public function __construct(
        private readonly AiGatewayService $ai,
    ) {
    }

    public function generate(string $scenario): PhishingTemplate
    {
        $systemPrompt = <<<'PROMPT'
            You are generating a realistic but clearly fictional phishing email
            for an authorised, internal corporate security awareness simulation.
            Respond ONLY with JSON: {"subject": string, "body": string}.
            The email should use common social-engineering techniques (urgency,
            authority, curiosity) appropriate to the given scenario. Do not
            reference any real company, person, or brand name.
            PROMPT;

        $rawJson = $this->ai->generate('phishing_generator', $systemPrompt, "Scenario: {$scenario}", maxTokens: 800);
        $parsed = json_decode($rawJson, associative: true);

        if (! is_array($parsed) || ! isset($parsed['subject'], $parsed['body'])) {
            throw new \RuntimeException('AI phishing template generation did not return the expected JSON shape.');
        }

        return PhishingTemplate::create([
            'tenant_id' => TenantContext::current(),
            'subject' => $parsed['subject'],
            'body' => $parsed['body'],
            'ai_generated' => true,
        ]);
    }
}
