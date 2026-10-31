<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes matching the listing's allow-listed ORDER BY clauses, including the id tie-breaker.
 * With 50k rows, EXPLAIN ANALYZE showed every unfiltered page doing a sequential scan plus a
 * top-N sort; these let PostgreSQL read pages in index order (scanned backwards for DESC).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->index(['last_name', 'first_name', 'id'], 'contacts_last_name_sort_index');
            $table->index(['first_name', 'last_name', 'id'], 'contacts_first_name_sort_index');
            $table->index(['created_at', 'id'], 'contacts_created_at_sort_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropIndex('contacts_last_name_sort_index');
            $table->dropIndex('contacts_first_name_sort_index');
            $table->dropIndex('contacts_created_at_sort_index');
        });
    }
};
