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
        Schema::create('contacts', function (Blueprint $table): void {
            // UUIDv7 primary key (approved deviation from an auto-increment ID). Exposed in API, CLI and SPA URLs.
            $table->uuid('id')->primary();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            // Stored trimmed and lower-cased. Unique: the normalised email is the business upsert key.
            $table->string('email', 254)->nullable()->unique();
            // Derived from email so domain search is an indexed equality match instead of LIKE '%@domain'.
            $table->string('email_domain', 253)->nullable()->index();
            // Canonical E.164 (+61 / +64).
            $table->string('phone', 16)->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE contacts ADD CONSTRAINT contacts_email_or_phone_check CHECK (email IS NOT NULL OR phone IS NOT NULL)');
        DB::statement('ALTER TABLE contacts ADD CONSTRAINT contacts_email_normalised_check CHECK (email = lower(btrim(email)))');
        DB::statement("ALTER TABLE contacts ADD CONSTRAINT contacts_phone_e164_check CHECK (phone ~ '^\\+6[14][0-9]{7,10}$')");
        DB::statement("ALTER TABLE contacts ADD CONSTRAINT contacts_names_present_check CHECK (btrim(first_name) <> '' AND btrim(last_name) <> '')");

        // Phone search is an E.164 prefix match (LIKE '+6141%'); text_pattern_ops lets LIKE use the btree. Also serves equality.
        DB::statement('CREATE INDEX contacts_phone_pattern_index ON contacts (phone text_pattern_ops)');

        // Name search is a case-insensitive prefix match on lower(first_name) / lower(last_name).
        DB::statement('CREATE INDEX contacts_first_name_lower_pattern_index ON contacts (lower(first_name) text_pattern_ops)');
        DB::statement('CREATE INDEX contacts_last_name_lower_pattern_index ON contacts (lower(last_name) text_pattern_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
