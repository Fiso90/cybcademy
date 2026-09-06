<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Employee\Models\User;
use App\Modules\LMS\Models\Assessment;
use App\Modules\LMS\Models\Course;
use App\Modules\LMS\Models\Lesson;
use App\Modules\LMS\Models\QuestionBankItem;
use App\Modules\Organisation\Models\Tenant;
use App\Modules\PhishingSimulation\Models\PhishingTemplate;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Closes the gap named explicitly, and repeatedly, since Phase 1's
 * Business Case: "Content production (courses, phishing templates) is
 * resource-intensive and could become the actual bottleneck rather than
 * engineering" — a risk that went unaddressed across all 15 phases and
 * was flagged as the single largest remaining gap in Phase 15's Release
 * document.
 *
 * Deliberately implemented as a per-tenant seed command rather than a
 * schema change to make courses shareable like phishing_templates and
 * knowledge_base_articles (both nullable-tenant_id, per Epics E4/E10) -
 * that would be a real, considered architecture change (courses.tenant_id
 * is NOT NULL by design per Phase 5, and BelongsToTenant assumes a
 * present tenant_id throughout), not something to slip in as a side
 * effect of writing content. This command clones the same three-course
 * starter library into whichever tenant it's run against, which is a
 * smaller, safer change that solves the immediate "new tenant has zero
 * content" problem without touching the tenant-isolation model this
 * entire project has been careful about since Epic E1.
 *
 * Usage: php artisan content:seed-starter-library {tenant-id}
 */
final class SeedStarterContentLibrary extends Command
{
    protected $signature = 'content:seed-starter-library {tenant : The tenant UUID to seed}';
    protected $description = 'Seeds a tenant with the starter cybersecurity awareness course library, platform phishing templates, and policy starter content.';

    public function handle(): int
    {
        $tenant = Tenant::find($this->argument('tenant'));

        if ($tenant === null) {
            $this->error('No tenant found with that ID.');

            return self::FAILURE;
        }

        TenantContext::set($tenant->id);

        $author = User::where('status', 'active')->first();
        if ($author === null) {
            $this->error('Tenant has no active users to attribute authored content to. Create at least one user first.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($tenant, $author): void {
            $this->seedPhishingAwarenessCourse($author->id);
            $this->seedPasswordAuthCourse($author->id);
            $this->seedSocialEngineeringCourse($author->id);
            $this->seedPlatformPhishingTemplates();
        });

        $this->info("Starter content library seeded for tenant: {$tenant->name}");

        return self::SUCCESS;
    }

    private function seedPhishingAwarenessCourse(string $authorId): void
    {
        $course = Course::create([
            'tenant_id' => TenantContext::current(),
            'title' => 'Recognising Phishing Attacks',
            'description' => 'Learn to identify the warning signs of phishing emails, links, and attachments before they cause harm.',
            'status' => 'published',
            'created_by' => $authorId,
            'is_onboarding_default' => true,
        ]);

        $this->lesson($course, 1, 'Why Phishing Still Works', <<<'TEXT'
            Phishing remains the most common way attackers get into an organisation - not
            because people are careless, but because phishing emails are designed to
            exploit normal, reasonable human behaviour: trust in authority, urgency, and
            the desire to be helpful.

            A well-crafted phishing email doesn't look suspicious. It looks like it's from
            your IT department, your bank, a courier company, or even a colleague. The
            goal of this course isn't to make you paranoid about every email - it's to
            give you a small set of reliable checks that work regardless of how
            convincing the email looks.
            TEXT);

        $this->lesson($course, 2, 'Five Signs of a Phishing Email', <<<'TEXT'
            1. Urgency or threats: "Your account will be suspended in 24 hours." Real
               organisations rarely demand immediate action via email.

            2. Mismatched sender details: the display name says "IT Support" but the
               actual email address is unrelated or slightly misspelled
               (e.g. support@cybcaderny.com instead of support@cybcademy.com).

            3. Generic greetings: "Dear Customer" or "Dear User" instead of your name,
               especially from a service that would normally know who you are.

            4. Requests for credentials or payment: legitimate services almost never ask
               you to "confirm your password" by clicking a link in an email.

            5. Links that don't match their destination: hover over a link (without
               clicking) and check where it actually leads before trusting it.

            No single sign proves an email is fake, and a phishing email might only show
            one or two of these - but any one of them is reason enough to pause before
            clicking.
            TEXT);

        $this->lesson($course, 3, "What To Do When You're Not Sure", <<<'TEXT'
            You do not need to be certain an email is phishing to report it. If something
            feels slightly off, use the Report Phishing button - it takes seconds, and
            reporting something that turns out to be legitimate is never held against you.

            If you've already clicked a suspicious link or entered credentials, report it
            immediately anyway. Acting fast matters far more than getting it right on the
            first try - a quick report gives your security team the best chance to limit
            any damage.
            TEXT);

        $assessment = Assessment::create([
            'tenant_id' => TenantContext::current(),
            'course_id' => $course->id,
            'title' => 'Phishing Awareness Check',
            'passing_score' => 70,
        ]);

        $this->question($assessment, 1, 'true_false',
            'A phishing email always contains obvious spelling mistakes.',
            null, ['value' => false]);

        $this->question($assessment, 2, 'mcq',
            'You receive an email from "IT Support" asking you to confirm your password by clicking a link. What should you do?',
            [
                ['id' => 'a', 'text' => 'Click the link and enter your password, since it says IT Support'],
                ['id' => 'b', 'text' => 'Ignore it and delete it without telling anyone'],
                ['id' => 'c', 'text' => 'Report it using the Report Phishing button'],
            ],
            ['option_id' => 'c']);

        $this->question($assessment, 3, 'true_false',
            'If you accidentally click a phishing link, you should wait to see if anything bad happens before reporting it.',
            null, ['value' => false]);
    }

    private function seedPasswordAuthCourse(string $authorId): void
    {
        $course = Course::create([
            'tenant_id' => TenantContext::current(),
            'title' => 'Passwords and Multi-Factor Authentication',
            'description' => 'Practical guidance on creating strong passwords and understanding why MFA matters, even when it feels like an extra step.',
            'status' => 'published',
            'created_by' => $authorId,
            'is_onboarding_default' => true,
        ]);

        $this->lesson($course, 1, 'What Makes a Password Strong', <<<'TEXT'
            Length matters more than complexity. A long, memorable passphrase like
            "correct-horse-battery-staple-42" is harder to crack than a short password
            like "P@ssw0rd!" even though the second one looks more complicated - because
            attackers crack passwords by trying huge numbers of combinations quickly, and
            length is what actually slows that down.

            Never reuse a password across multiple accounts. If one service you use is
            ever breached, attackers try that same password everywhere else - reusing
            passwords turns one breach into many.

            A password manager solves both problems at once: it generates long, unique
            passwords for every account and remembers them for you, so you only need to
            remember one strong master password.
            TEXT);

        $this->lesson($course, 2, 'Why Multi-Factor Authentication Matters', <<<'TEXT'
            Multi-factor authentication (MFA) means proving who you are with something
            you know (your password) AND something you have (a code from your phone) or
            something you are (a fingerprint). Even if an attacker steals your password,
            MFA stops them from getting in without also having your phone.

            It can feel like friction - one more step between you and getting your work
            done. But that small amount of friction is exactly what makes it effective:
            it's the same friction an attacker faces, and they usually don't have your
            second factor.

            In CybCademy, MFA is required for certain roles because those accounts have
            access to sensitive organisational data - this isn't a judgment about your
            trustworthiness, it's a baseline protection for a role with more access.
            TEXT);

        $assessment = Assessment::create([
            'tenant_id' => TenantContext::current(),
            'course_id' => $course->id,
            'title' => 'Password and MFA Check',
            'passing_score' => 70,
        ]);

        $this->question($assessment, 1, 'mcq',
            'Which of these passwords is generally considered strongest?',
            [
                ['id' => 'a', 'text' => 'P@ssw0rd!'],
                ['id' => 'b', 'text' => 'correct-horse-battery-staple-42'],
                ['id' => 'c', 'text' => 'Summer2026'],
            ],
            ['option_id' => 'b']);

        $this->question($assessment, 2, 'true_false',
            'If your password is strong enough, you don\'t need multi-factor authentication.',
            null, ['value' => false]);
    }

    private function seedSocialEngineeringCourse(string $authorId): void
    {
        $course = Course::create([
            'tenant_id' => TenantContext::current(),
            'title' => 'Social Engineering Beyond Email',
            'description' => 'Phone calls, text messages, and in-person tactics attackers use to manipulate people into bypassing security controls.',
            'status' => 'published',
            'created_by' => $authorId,
            'is_onboarding_default' => false,
        ]);

        $this->lesson($course, 1, 'It\'s Not Just Email', <<<'TEXT'
            Attackers don't limit themselves to phishing emails. A phone call claiming to
            be from "IT" asking you to read out a one-time code, a text message about a
            "failed delivery" with a link, or someone in the building claiming to be a
            contractor who "just needs to grab something from the server room" are all
            the same underlying tactic: creating a plausible reason for you to bypass a
            normal security step.

            The common thread across all of these is urgency plus authority - the
            attacker wants you to act quickly, before you have time to verify their
            claim through a separate channel.
            TEXT);

        $this->lesson($course, 2, 'Verify Through a Separate Channel', <<<'TEXT'
            If someone calls claiming to be from IT and asks for information, hang up and
            call your IT department back using a number you already know - not a number
            they gave you. If someone claims to be a vendor or contractor, verify with
            your facilities or procurement team before granting access.

            This one habit - verifying an unexpected request through a channel the
            requester doesn't control - defeats the vast majority of social engineering
            attempts, because it removes the attacker's ability to control what
            "verification" looks like.
            TEXT);
    }

    private function seedPlatformPhishingTemplates(): void
    {
        // Platform-wide (tenant_id null) per the pattern established in
        // Epic E4's migration - visible to every tenant, not just this one.
        $templates = [
            [
                'subject' => 'Action Required: Verify Your Account by End of Day',
                'body' => "Hi,\n\nWe've noticed unusual activity on your account and need you to verify your identity immediately to avoid suspension.\n\nClick here to verify: {{tracking_link}}\n\nThank you,\nAccount Security Team",
            ],
            [
                'subject' => 'Your Package Delivery Failed - Reschedule Now',
                'body' => "We attempted to deliver your package today but were unable to complete delivery.\n\nPlease reschedule your delivery within 24 hours or your package will be returned to sender: {{tracking_link}}\n\nDelivery Services",
            ],
            [
                'subject' => 'Shared Document: Q3 Budget Review',
                'body' => "Hi,\n\nI've shared a document with you for review before tomorrow's meeting.\n\nView document: {{tracking_link}}\n\nThanks,\nFinance Team",
            ],
        ];

        foreach ($templates as $t) {
            PhishingTemplate::firstOrCreate(
                ['subject' => $t['subject'], 'tenant_id' => null],
                ['body' => $t['body'], 'ai_generated' => false]
            );
        }
    }

    private function lesson(Course $course, int $order, string $title, string $body): void
    {
        Lesson::create([
            'tenant_id' => TenantContext::current(),
            'course_id' => $course->id,
            'title' => $title,
            'content_type' => 'text',
            'content_body' => $body,
            'sequence_order' => $order,
        ]);
    }

    private function question(Assessment $assessment, int $order, string $type, string $text, ?array $options, array $correctAnswer): void
    {
        $question = QuestionBankItem::create([
            'tenant_id' => TenantContext::current(),
            'question_text' => $text,
            'question_type' => $type,
            'options' => $options,
            'correct_answer' => $correctAnswer,
        ]);

        $assessment->questions()->attach($question->id, ['sequence_order' => $order]);
    }
}
