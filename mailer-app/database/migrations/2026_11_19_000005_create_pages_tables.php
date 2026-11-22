<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vues : pages publiques autonomes créées par l'admin (adresse /vue/{slug}).
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('form')->nullable();           // titre, intro, bouton, champs (via ResponseForm)
            $table->string('logo_path')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        // Réponses déposées sur une vue (anonymes : pas de compte, pas de jeton).
        Schema::create('page_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_responses');
        Schema::dropIfExists('pages');
    }
};
