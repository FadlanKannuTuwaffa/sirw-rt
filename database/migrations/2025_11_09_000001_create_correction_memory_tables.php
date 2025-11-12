<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assistant_correction_events')) {
            Schema::create('assistant_correction_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('org_id')->nullable();
                $table->string('thread_id', 64)->nullable();
                $table->string('turn_id', 64)->nullable();
                $table->enum('correction_type', ['fakta', 'gaya', 'bahasa', 'persona', 'langkah', 'istilah', 'lainnya'])->default('lainnya');
                $table->enum('scope', ['global', 'org', 'user', 'thread'])->default('user');
                $table->longText('original_input')->nullable();
                $table->longText('original_answer')->nullable();
                $table->longText('user_feedback_raw');
                $table->json('normalized_instruction')->nullable();
                $table->json('patch_rules')->nullable();
                $table->string('language_preference', 10)->nullable();
                $table->string('tone_preference', 10)->nullable();
                $table->json('examples')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->timestamp('applied_at')->nullable();

                $table->index(['user_id', 'is_active'], 'idx_ace_user');
                $table->index(['scope', 'is_active'], 'idx_ace_scope');
                $table->index('correction_type', 'idx_ace_type');
            });
        }

        if (!Schema::hasTable('lexicon_slang')) {
            Schema::create('lexicon_slang', function (Blueprint $table) {
                $table->string('term', 64)->primary();
                $table->string('canonical', 128);
                $table->string('region', 64)->nullable();
                $table->json('examples')->nullable();
                $table->unsignedInteger('times_seen')->default(0);
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_style_prefs')) {
            Schema::create('user_style_prefs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index('idx_usp_user');
                $table->unsignedBigInteger('org_id')->nullable()->index('idx_usp_org');
                $table->string('default_language', 10)->default('id');
                $table->string('formality', 10)->default('santai');
                $table->boolean('humor')->default(true);
                $table->boolean('introduce_self_on_first_turn')->default(true);
                $table->string('emoji_policy', 10)->default('light');
                $table->timestamps();
            });
        }

        Schema::table('assistant_interaction_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('assistant_interaction_logs', 'provider_primary')) {
                $table->string('provider_primary', 64)->nullable()->after('llm_provider');
            }
            if (!Schema::hasColumn('assistant_interaction_logs', 'provider_final')) {
                $table->string('provider_final', 64)->nullable()->after('provider_primary');
            }
            if (!Schema::hasColumn('assistant_interaction_logs', 'provider_fallback_from')) {
                $table->string('provider_fallback_from', 64)->nullable()->after('provider_final');
            }
            if (!Schema::hasColumn('assistant_interaction_logs', 'repetition_score')) {
                $table->double('repetition_score')->nullable()->after('success');
            }
            if (!Schema::hasColumn('assistant_interaction_logs', 'correction_event_id')) {
                $table->foreignId('correction_event_id')
                    ->nullable()
                    ->after('tool_success')
                    ->constrained('assistant_correction_events')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('assistant_interaction_logs', 'smalltalk_kind')) {
                $table->string('smalltalk_kind', 24)->nullable()->after('classification_type');
            }
        });

        if (Schema::hasTable('lexicon_slang')) {
            DB::table('lexicon_slang')->upsert([
                [
                    'term' => 'bro',
                    'canonical' => 'kamu',
                    'region' => 'id',
                    'examples' => json_encode(['bro bisa bantu?']),
                    'times_seen' => 3,
                    'last_seen_at' => now(),
                ],
                [
                    'term' => 'bray',
                    'canonical' => 'kamu',
                    'region' => 'id',
                    'examples' => json_encode(['santai ya bray']),
                    'times_seen' => 1,
                    'last_seen_at' => now(),
                ],
                [
                    'term' => 'cuy',
                    'canonical' => 'kamu',
                    'region' => 'id',
                    'examples' => json_encode(['halo cuy']),
                    'times_seen' => 2,
                    'last_seen_at' => now(),
                ],
            ], ['term'], ['canonical', 'region', 'examples', 'times_seen', 'last_seen_at', 'updated_at']);
        }
    }

    public function down(): void
    {
        Schema::table('assistant_interaction_logs', function (Blueprint $table) {
            if (Schema::hasColumn('assistant_interaction_logs', 'provider_primary')) {
                $table->dropColumn('provider_primary');
            }
            if (Schema::hasColumn('assistant_interaction_logs', 'provider_final')) {
                $table->dropColumn('provider_final');
            }
            if (Schema::hasColumn('assistant_interaction_logs', 'provider_fallback_from')) {
                $table->dropColumn('provider_fallback_from');
            }
            if (Schema::hasColumn('assistant_interaction_logs', 'repetition_score')) {
                $table->dropColumn('repetition_score');
            }
            if (Schema::hasColumn('assistant_interaction_logs', 'smalltalk_kind')) {
                $table->dropColumn('smalltalk_kind');
            }
            if (Schema::hasColumn('assistant_interaction_logs', 'correction_event_id')) {
                $table->dropConstrainedForeignId('correction_event_id');
            }
        });

        Schema::dropIfExists('user_style_prefs');
        Schema::dropIfExists('lexicon_slang');
        Schema::dropIfExists('assistant_correction_events');
    }
};
