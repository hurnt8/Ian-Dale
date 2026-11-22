<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('is_default'); // proposé aux propets
            $table->string('logo_path')->nullable()->after('body');            // logo propre à ce message
            $table->string('button_text', 80)->nullable()->after('logo_path');
            $table->string('button_url', 500)->nullable()->after('button_text');
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->string('logo_path')->nullable()->after('body');
            $table->string('button_text', 80)->nullable()->after('logo_path');
            $table->string('button_url', 500)->nullable()->after('button_text');
        });

        Schema::table('campaign_recipients', function (Blueprint $table): void {
            $table->string('verification', 24)->nullable()->index()->after('status');
            $table->string('verification_detail')->nullable()->after('verification');
        });

        Schema::table('unsubscribes', function (Blueprint $table): void {
            $table->string('reason', 24)->default('unsubscribed')->after('email'); // unsubscribed | hard_bounce
        });
    }

    public function down(): void
    {
        Schema::table('unsubscribes', fn (Blueprint $t) => $t->dropColumn('reason'));
        Schema::table('campaign_recipients', fn (Blueprint $t) => $t->dropColumn(['verification', 'verification_detail']));
        Schema::table('campaigns', fn (Blueprint $t) => $t->dropColumn(['logo_path', 'button_text', 'button_url']));
        Schema::table('message_templates', fn (Blueprint $t) => $t->dropColumn(['is_active', 'logo_path', 'button_text', 'button_url']));
    }
};
