<?php

declare(strict_types=1);

/**
 * Excerpt of config/services.php showing only the addition made for
 * Epic E9. Merge into the existing config file rather than replacing it.
 *
 * Model identifier is environment-configurable rather than hardcoded in
 * AiGatewayService, specifically so picking up a new Claude model does
 * not require an application code change/deploy - see
 * app/Modules/AI/Services/AiGatewayService.php's docblock.
 */
return [
    // ...existing services config (mail, aws, etc.)...

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'api_version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
    ],
];
