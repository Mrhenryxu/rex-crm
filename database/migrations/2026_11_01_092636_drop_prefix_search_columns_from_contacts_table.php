<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Search changed from prefix/exact matching to contains matching (LIKE '%term%') on name, phone and email.
 * A leading wildcard cannot use btree indexes, so the prefix indexes and the derived email_domain column
 * no longer serve any query and only add write cost. The sort indexes are unaffected.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS contacts_phone_pattern_index');
        DB::statement('DROP INDEX IF EXISTS contacts_first_name_lower_pattern_index');
        DB::statement('DROP INDEX IF EXISTS contacts_last_name_lower_pattern_index');

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropIndex(['email_domain']);
            $table->dropColumn('email_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->string('email_domain', 253)->nullable()->index();
        });

        DB::statement("UPDATE contacts SET email_domain = NULLIF(split_part(email, '@', 2), '') WHERE email IS NOT NULL");
        DB::statement('CREATE INDEX contacts_phone_pattern_index ON contacts (phone text_pattern_ops)');
        DB::statement('CREATE INDEX contacts_first_name_lower_pattern_index ON contacts (lower(first_name) text_pattern_ops)');
        DB::statement('CREATE INDEX contacts_last_name_lower_pattern_index ON contacts (lower(last_name) text_pattern_ops)');
    }
};
