<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Messages autorisés par agent (propet). Vide pour un agent = tous les messages actifs.
        Schema::create('message_template_user', function (Blueprint $table) {
            $table->foreignId('message_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['message_template_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_template_user');
    }
};
