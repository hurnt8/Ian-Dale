<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Auteur du message : null = message de l'admin (partagé), sinon message personnel d'un agent.
        if (Schema::hasColumn('message_templates', 'user_id')) {
            return;
        }

        Schema::table('message_templates', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
