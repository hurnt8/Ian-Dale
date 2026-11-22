<?php

namespace Tests\Feature;

use App\Enums\RecipientStatus;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Support\OrangeDomains;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrangeOnlyTest extends TestCase
{
    use RefreshDatabase;

    private MessageTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->template = MessageTemplate::create([
            'name' => 'Principal', 'subject' => 'Sujet', 'body' => 'Corps', 'is_default' => true, 'is_active' => true,
        ]);
        Setting::put('orange_only', '1');   // réglage par défaut en production
        Setting::put('require_approval', '0');
    }

    private function submit(string $emails): Campaign
    {
        $this->actingAs(User::factory()->create())->post('/propet/campagnes', [
            'title' => 'Liste', 'message_template_id' => $this->template->id, 'emails' => $emails,
        ])->assertRedirect();

        return Campaign::firstOrFail();
    }

    public function test_only_orange_recipients_receive_the_mail(): void
    {
        Mail::fake();

        $this->submit('client@orange.fr, abonne@wanadoo.fr, autre@gmail.com');

        // orange.fr et wanadoo.fr -> envoyés ; gmail.com -> écarté
        Mail::assertSent(CampaignMail::class, 2);
        Mail::assertSent(CampaignMail::class, fn ($m) => $m->hasTo('client@orange.fr'));
        Mail::assertSent(CampaignMail::class, fn ($m) => $m->hasTo('abonne@wanadoo.fr'));
        Mail::assertNotSent(CampaignMail::class, fn ($m) => $m->hasTo('autre@gmail.com'));

        $gmail = CampaignRecipient::where('email', 'autre@gmail.com')->firstOrFail();
        $this->assertSame(RecipientStatus::Skipped, $gmail->status);
        $this->assertStringContainsString('Hors Orange', (string) $gmail->error);
    }

    public function test_the_list_of_orange_domains_is_configurable(): void
    {
        Mail::fake();
        Setting::put('orange_domains', 'gmail.com'); // on autorise un autre domaine

        $this->submit('client@orange.fr, autre@gmail.com');

        // Désormais seul gmail.com est « Orange »
        $this->assertTrue(OrangeDomains::allows('x@gmail.com'));
        $this->assertFalse(OrangeDomains::allows('x@orange.fr'));
        Mail::assertSent(CampaignMail::class, 1);
        Mail::assertSent(CampaignMail::class, fn ($m) => $m->hasTo('autre@gmail.com'));
        Mail::assertNotSent(CampaignMail::class, fn ($m) => $m->hasTo('client@orange.fr'));
    }

    public function test_when_disabled_everyone_receives_the_mail(): void
    {
        Mail::fake();
        Setting::put('orange_only', '0');

        $this->submit('client@orange.fr, autre@gmail.com');

        Mail::assertSent(CampaignMail::class, 2);
    }
}
