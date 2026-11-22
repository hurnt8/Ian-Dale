<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Formulaire de réponse (avis…) configurable par message, copié dans la liste à la soumission
        Schema::table('message_templates', function (Blueprint $table): void {
            $table->json('form')->nullable()->after('button_url');
        });
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->json('form')->nullable()->after('button_url');
        });

        // Jeton personnel : le lien de l'email identifie le destinataire sans qu'il ressaisisse son adresse
        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->string('token', 48)->nullable()->unique()->after('email');
            $table->timestamp('form_opened_at')->nullable()->after('sent_at');
        });

        DB::table('campaign_recipients')->whereNull('token')->orderBy('id')->each(function ($r): void {
            DB::table('campaign_recipients')->where('id', $r->id)->update(['token' => Str::random(48)]);
        });

        Schema::create('responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_recipient_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email');                // copie : retrouvée automatiquement via le jeton
            $table->json('answers');                // [{key, label, type, value}]
            $table->unsignedInteger('edits')->default(0);
            $table->timestamps();
            $table->index(['campaign_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responses');
        Schema::table('campaign_recipients', fn (Blueprint $t) => $t->dropColumn(['token', 'form_opened_at']));
        Schema::table('campaigns', fn (Blueprint $t) => $t->dropColumn('form'));
        Schema::table('message_templates', fn (Blueprint $t) => $t->dropColumn('form'));
    }
};
