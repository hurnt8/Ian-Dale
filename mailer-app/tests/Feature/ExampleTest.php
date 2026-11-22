<?php

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\RecipientStatus;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\Unsubscribe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MessageTemplate::create([
            'name' => 'Défaut',
            'subject' => 'Bonjour {email}',
            'body' => 'Message pour {email}',
            'is_default' => true,
        ]);
    }

    public function test_roles_are_isolated(): void
    {
        $admin = User::factory()->admin()->create();
        $propet = User::factory()->create();

        $this->actingAs($propet)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/propet/campagnes')->assertForbidden();
        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($propet)->get('/')->assertRedirect(route('propet.campaigns.index'));
        $this->get('/logout')->assertStatus(405);
    }

    public function test_propet_submission_waits_for_admin_then_sends_automatically(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $propet = User::factory()->create();
        Unsubscribe::create(['email' => 'stop@example.fr']);

        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'Test', 'message_template_id' => MessageTemplate::first()->id,
            'emails' => "a@example.fr, b@example.fr\nstop@example.fr, invalide, a@example.fr",
        ])->assertRedirect();

        $campaign = Campaign::firstOrFail();
        $this->assertSame(CampaignStatus::Pending, $campaign->status);
        $this->assertSame(3, $campaign->total);
        Mail::assertNothingSent();

        // Validation admin → envoi (queue sync en test)
        $this->actingAs($admin)->post(route('admin.campaigns.approve', $campaign))->assertRedirect();

        Mail::assertSent(CampaignMail::class, 2);
        Mail::assertSent(CampaignMail::class, fn (CampaignMail $m) => $m->hasTo('a@example.fr'));
        Mail::assertNotSent(CampaignMail::class, fn (CampaignMail $m) => $m->hasTo('stop@example.fr'));

        $campaign->refresh();
        $this->assertSame(CampaignStatus::Completed, $campaign->status);
        $this->assertSame(['pending' => 0, 'sent' => 2, 'failed' => 0, 'skipped' => 1, 'invalid' => 0], $campaign->stats());
    }

    public function test_sends_immediately_when_approval_disabled(): void
    {
        Mail::fake();
        Setting::put('require_approval', '0');
        $propet = User::factory()->create();

        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'Direct', 'message_template_id' => MessageTemplate::first()->id, 'emails' => 'x@example.fr, y@example.fr',
        ]);

        Mail::assertSent(CampaignMail::class, 2);
        $this->assertSame(CampaignStatus::Completed, Campaign::first()->status);
    }

    public function test_rejects_lists_over_limit(): void
    {
        Setting::put('max_recipients', '2');
        $propet = User::factory()->create();

        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'Trop', 'message_template_id' => MessageTemplate::first()->id, 'emails' => 'a@e.fr b@e.fr c@e.fr',
        ])->assertSessionHasErrors('emails');

        $this->assertSame(0, Campaign::count());
    }

    public function test_propet_cannot_view_another_propets_campaign(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $campaign = Campaign::create([
            'user_id' => $owner->id, 'message_template_id' => MessageTemplate::first()->id,
            'title' => 'x', 'subject' => 's', 'body' => 'b', 'status' => CampaignStatus::Pending, 'total' => 0,
        ]);

        $this->actingAs($other)->get(route('propet.campaigns.show', $campaign))->assertForbidden();
        $this->actingAs($owner)->get(route('propet.campaigns.show', $campaign))->assertOk();
    }

    public function test_mail_contains_placeholders_and_unsubscribe_link(): void
    {
        $campaign = Campaign::create([
            'user_id' => User::factory()->create()->id, 'message_template_id' => MessageTemplate::first()->id,
            'title' => 'x', 'subject' => 'Bonjour {email}', 'body' => 'Message pour {email}',
            'status' => CampaignStatus::Sending, 'total' => 1,
        ]);

        $mail = new CampaignMail($campaign, 'z@example.fr');
        $mail->assertHasSubject('Bonjour z@example.fr');
        $mail->assertSeeInHtml('Message pour z@example.fr');
        $mail->assertSeeInHtml('desinscription');
    }

    public function test_unsubscribe_requires_signature_and_records_email(): void
    {
        $this->get('/desinscription?email=a@example.fr')->assertForbidden();

        $url = URL::signedRoute('unsubscribe.store', ['email' => 'a@example.fr']);
        $this->post($url)->assertOk();

        $this->assertTrue(Unsubscribe::isUnsubscribed('a@example.fr'));
    }

    public function test_login_works_and_inactive_users_are_refused(): void
    {
        User::factory()->create(['email' => 'ok@example.fr']);
        User::factory()->create(['email' => 'off@example.fr', 'is_active' => false]);

        $this->post('/login', ['email' => 'off@example.fr', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'ok@example.fr', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        foreach (['/admin', '/admin/campagnes', '/admin/messages', '/admin/messages/create', '/admin/utilisateurs', '/admin/utilisateurs/create', '/admin/parametres'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_propet_sees_only_own_emails_admin_sees_all(): void
    {
        $admin = User::factory()->admin()->create();
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);

        foreach ([[$alice, 'alice-only@example.fr'], [$bob, 'bob-only@example.fr']] as [$user, $email]) {
            $c = Campaign::create([
                'user_id' => $user->id, 'message_template_id' => MessageTemplate::first()->id,
                'title' => 'Liste '.$user->name, 'subject' => 's', 'body' => 'b',
                'status' => CampaignStatus::Pending, 'total' => 1,
            ]);
            $c->recipients()->create(['email' => $email, 'status' => RecipientStatus::Pending]);
        }

        // Propet : seulement ses adresses, même en forçant un filtre sur la liste de l'autre
        $this->actingAs($alice)->get('/propet/emails')
            ->assertOk()->assertSee('alice-only@example.fr')->assertDontSee('bob-only@example.fr')->assertDontSee('Liste Bob');
        $bobCampaign = Campaign::where('user_id', $bob->id)->first();
        $this->actingAs($alice)->get('/propet/emails?campaign='.$bobCampaign->id)->assertDontSee('bob-only@example.fr');
        $this->actingAs($alice)->get('/propet/campagnes')->assertSee('Liste Alice')->assertDontSee('Liste Bob');

        // Propet : aucun accès aux pages admin ni à l'export
        $this->actingAs($alice)->get('/admin/emails')->assertForbidden();
        $this->actingAs($alice)->get('/admin/emails/export')->assertForbidden();

        // Admin : la totalité, filtrable par propet, exportable
        $this->actingAs($admin)->get('/admin/emails')->assertOk()->assertSee('alice-only@example.fr')->assertSee('bob-only@example.fr');
        $this->actingAs($admin)->get('/admin/emails?propet='.$bob->id)->assertSee('bob-only@example.fr')->assertDontSee('alice-only@example.fr');
        $csv = $this->actingAs($admin)->get('/admin/emails/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('alice-only@example.fr', $csv);
        $this->assertStringContainsString('bob-only@example.fr', $csv);
    }

    public function test_all_pages_render_with_data(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $propet = User::factory()->create();
        $this->actingAs($propet)->post('/propet/campagnes', ['title' => 'Rendu', 'message_template_id' => MessageTemplate::first()->id, 'emails' => 'a@example.fr, b@example.fr']);
        $c = Campaign::first();

        foreach (['/propet/campagnes', '/propet/campagnes/nouvelle', '/propet/emails', "/propet/campagnes/{$c->id}"] as $url) {
            $this->actingAs($propet)->get($url)->assertOk();
        }
        foreach (['/admin', "/admin/campagnes/{$c->id}", '/admin/emails?status=pending&q=a', "/admin/messages/{$c->message_template_id}/edit", "/admin/utilisateurs/{$propet->id}/edit"] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->get('/login')->assertRedirect(); // déjà connecté
    }

    public function test_guest_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Se connecter');
    }

    public function test_failed_job_marks_recipient_failed(): void
    {
        $campaign = Campaign::create([
            'user_id' => User::factory()->create()->id, 'message_template_id' => MessageTemplate::first()->id,
            'title' => 'x', 'subject' => 's', 'body' => 'b', 'status' => CampaignStatus::Sending, 'total' => 1,
        ]);
        $r = $campaign->recipients()->create(['email' => 'f@example.fr', 'status' => RecipientStatus::Pending]);

        (new \App\Jobs\SendCampaignEmail($r->id))->failed(new \RuntimeException('SMTP down'));

        $this->assertSame(RecipientStatus::Failed, $r->fresh()->status);
        $this->assertSame(CampaignStatus::Completed, $campaign->fresh()->status);
    }
}
