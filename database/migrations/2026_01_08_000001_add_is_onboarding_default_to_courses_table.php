<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `courses.is_onboarding_default`.
 *
 * This closes a gap that has been an open, named forward-reference since
 * Epic E2's own README (Phase 10): "AssignOnboardingCourses queries
 * courses.is_onboarding_default, a column not yet in the Epic E2
 * migrations — the actual tenant-configurable 'onboarding bundle'
 * mechanism is a Settings-module (Epic E10) concern; this listener
 * assumes that column will exist by the time E10 lands." Epic E10
 * shipped without adding it (billing/notifications/knowledge base took
 * priority in that drop) - it lands here, in the content-library work
 * that finally needed it to be real rather than aspirational.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->boolean('is_onboarding_default')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn('is_onboarding_default');
        });
    }
};
