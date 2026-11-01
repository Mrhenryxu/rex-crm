<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contact_calls', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // Deleting a contact deletes its call history.
            $table->foreignUuid('contact_id')->constrained('contacts')->cascadeOnDelete();
            // Number dialled, snapshotted so history survives later phone edits.
            $table->string('phone', 16);
            $table->string('status', 20);
            $table->string('outcome', 20)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Latest calls for a contact: WHERE contact_id = ? ORDER BY created_at DESC LIMIT 10.
            $table->index(['contact_id', 'created_at']);
        });

        DB::statement("ALTER TABLE contact_calls ADD CONSTRAINT contact_calls_status_check CHECK (status IN ('pending', 'completed', 'failed'))");
        DB::statement("ALTER TABLE contact_calls ADD CONSTRAINT contact_calls_outcome_check CHECK (outcome IS NULL OR outcome IN ('answered', 'busy', 'no_answer', 'voicemail', 'failed'))");
        DB::statement("ALTER TABLE contact_calls ADD CONSTRAINT contact_calls_completed_outcome_check CHECK (status <> 'completed' OR outcome IS NOT NULL)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_calls');
    }
};
