<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agent dont le lien personnel a servi à répondre (null = lien général de la vue).
        if (Schema::hasColumn('page_responses', 'user_id')) {
            return;
        }

        Schema::table('page_responses', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('page_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('page_responses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
