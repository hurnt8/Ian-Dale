<?php

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\RecipientStatus;
use App\Enums\VerificationStatus;
use App\Jobs\SendCampaignEmail;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\Unsubscribe;
use App\Models\User;
use App\Services\Verification\EmailVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationAndBrandingTest extends TestCase
{
    use RefreshDatabase;

    private MessageTemplate $main;
    private MessageTemplate $other;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->main = MessageTemplate::create(['name' => 'Principal', 'subject' => 'Sujet A', 'body' => 'Corps A', 'is_default' => true, 'is_active' => true]);
        $this->other = MessageTemplate::create(['name' => 'Invitation', 'subject' => 'Sujet B', 'body' => 'Corps B', 'is_active' => true]);
    }

    // ---------- Vérification ----------

    public function test_verifier_classifies_addresses(): void
    {
        $v = app(EmailVerifier::class);

        $this->assertSame(VerificationStatus::Valid, $v->verify('jean@example.fr')->status);
        $this->assertSame(VerificationStatus::Valid, $v->verify('Jean@Orange.fr')->status);
        $this->assertSame(VerificationStatus::InvalidSyntax, $v->verify('jean@@example.fr')->status);
        $this->assertSame(VerificationStatus::DomainNotFound, $v->verify('jean@domaine-qui-nexiste-pas.fr')->status);
        $this->assertSame(VerificationStatus::Risky, $v->verify('jean@site-sans-mail.fr')->status);
        $this->assertSame(VerificationStatus::Disposable, $v->verify('jean@yopmail.com')->status);

        $typo = $v->verify('jean@gmial.com');
        $this->assertSame(VerificationStatus::Typo, $typo->status);
        $this->assertSame('jean@gmail.com', $typo->suggestion);

        $this->assertSame(VerificationStatus::Typo, $v->verify('jean@orange.fe')->status);
    }

    public function test_verification_endpoint_returns_results_for_both_roles(): void
    {
        Unsubscribe::create(['email' => 'mort@example.fr', 'reason' => 'hard_bounce']);

        foreach ([User::factory()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->get('/verification')->assertOk()->assertSee('Vérifier');

            $res = $this->actingAs($user)->postJson('/verification', [
                'emails' => "ok@example.fr, faux@gmial.com\npas-une-adresse, x@nulle-part.fr, mort@example.fr, ok@example.fr",
            ])->assertOk()->json();

            $byEmail = collect($res['results'])->keyBy('email');
            $this->assertSame('valid', $byEmail['ok@example.fr']['status']);
            $this->assertSame('typo', $byEmail['faux@gmial.com']['status']);
            $this->assertSame('faux@gmail.com', $byEmail['faux@gmial.com']['suggestion']);
            $this->assertSame('invalid_syntax', $byEmail['pas-une-adresse']['status']);
            $this->assertSame('domain_not_found', $byEmail['x@nulle-part.fr']['status']);
            $this->assertSame('mailbox_not_found', $byEmail['mort@example.fr']['status']);
            $this->assertSame(1, $res['duplicates']);
            $this->assertSame(2, $res['sendable']); // ok@ + faute de frappe (non bloquante)
        }

        $this->post('/logout');
        $this->postJson('/verification', ['emails' => 'a@example.fr'])->assertUnauthorized();
    }

    public function test_invalid_addresses_are_not_sent(): void
    {
        Mail::fake();
        Setting::put('require_approval', '0');

        $this->actingAs(User::factory()->create())->post('/propet/campagnes', [
            'title' => 'Mixte', 'message_template_id' => $this->main->id,
            'emails' => 'bon@example.fr, perdu@domaine-inconnu.fr, jetable@yopmail.com',
        ])->assertRedirect();

        Mail::assertSent(CampaignMail::class, 1);
        Mail::assertSent(CampaignMail::class, fn ($m) => $m->hasTo('bon@example.fr'));

        $c = Campaign::first();
        $this->assertSame(CampaignStatus::Completed, $c->status);
        $this->assertSame(2, $c->stats()['invalid']);
        $this->assertSame(VerificationStatus::DomainNotFound, $c->recipients()->where('email', 'perdu@domaine-inconnu.fr')->first()->verification);
    }

    public function test_hard_bounce_marks_mailbox_not_found_and_suppresses(): void
    {
        $this->assertTrue(SendCampaignEmail::isHardBounce('Expected response code "250" but got "550 5.1.1 <x@y.fr>: Recipient address rejected: User unknown"'));
        $this->assertFalse(SendCampaignEmail::isHardBounce('Connection timed out'));

        $c = Campaign::create([
            'user_id' => User::factory()->create()->id, 'message_template_id' => $this->main->id,
            'title' => 't', 'subject' => 's', 'body' => 'b', 'status' => CampaignStatus::Sending, 'total' => 1,
        ]);
        $r = $c->recipients()->create(['email' => 'inconnu@example.fr', 'status' => RecipientStatus::Pending]);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('550 5.1.1 User unknown'));
        (new SendCampaignEmail($r->id))->handle();

        $r->refresh();
        $this->assertSame(RecipientStatus::Invalid, $r->status);
        $this->assertSame(VerificationStatus::MailboxNotFound, $r->verification);
        $this->assertSame('hard_bounce', Unsubscribe::where('email', 'inconnu@example.fr')->value('reason'));
        $this->assertSame(CampaignStatus::Completed, $c->fresh()->status);
    }

    // ---------- Choix du message par le propet ----------

    public function test_propet_chooses_message_among_active_ones(): void
    {
        Mail::fake();
        $propet = User::factory()->create();
        $inactive = MessageTemplate::create(['name' => 'Caché', 'subject' => 'Sujet caché', 'body' => 'x', 'is_active' => false]);

        $this->actingAs($propet)->get('/propet/campagnes/nouvelle')
            ->assertOk()->assertSee('Principal')->assertSee('Invitation')->assertDontSee('Sujet caché');

        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'Choix', 'message_template_id' => $this->other->id, 'emails' => 'a@example.fr',
        ])->assertRedirect();
        $this->assertSame('Sujet B', Campaign::first()->subject);

        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'Interdit', 'message_template_id' => $inactive->id, 'emails' => 'a@example.fr',
        ])->assertSessionHasErrors('message_template_id');

        $this->actingAs($propet)->get(route('preview.template', $inactive))->assertNotFound();
        $this->actingAs($propet)->get(route('preview.template', $this->other))->assertOk()->assertSee('Corps B');
    }

    // ---------- Présentation + logo ----------

    public function test_admin_defines_presentation_and_logo_is_embedded(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/presentation', [
            'logo' => UploadedFile::fake()->image('logo.png', 300, 100),
            'mail_logo_width' => 180, 'mail_logo_align' => 'left',
            'mail_primary_color' => '#ff6600', 'mail_bg_color' => '#eeeeee', 'mail_card_color' => '#ffffff', 'mail_text_color' => '#111111',
            'mail_font' => 'Georgia, serif', 'mail_header_text' => 'Lettre de la mairie', 'mail_footer_text' => 'Mairie de Test',
        ])->assertSessionHasNoErrors();

        $logo = Setting::get('mail_logo_path');
        $this->assertNotNull($logo);
        Storage::disk('local')->assertExists($logo);

        // Aperçu
        $this->actingAs($admin)->get(route('preview.template', $this->main))
            ->assertOk()->assertSee('#ff6600')->assertSee('Lettre de la mairie')->assertSee('Mairie de Test')->assertSee('data:image/png;base64', false);

        // Aperçu en direct (valeurs non enregistrées) ; valeurs invalides ignorées
        $this->actingAs($admin)->get(route('preview.template', $this->main).'?mail_primary_color=%23123456&mail_bg_color=red;}')
            ->assertSee('#123456')->assertDontSee('red;}');

        // Envoi réel (transport "array") : le logo est une pièce jointe inline (CID)
        $c = Campaign::create([
            'user_id' => User::factory()->create()->id, 'message_template_id' => $this->main->id,
            'title' => 't', 'subject' => 'Sujet', 'body' => 'Bonjour', 'status' => CampaignStatus::Sending, 'total' => 1,
        ]);
        Mail::to('dest@example.fr')->send(new CampaignMail($c, 'dest@example.fr'));

        $sent = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertCount(1, $sent->getAttachments());
        $this->assertSame('inline', $sent->getAttachments()[0]->getDisposition());
        $this->assertStringContainsString('cid:', $sent->getHtmlBody());
        $this->assertStringContainsString('#ff6600', $sent->getHtmlBody());
    }

    public function test_logo_label_and_info_block_render_in_the_email(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put('/admin/presentation', [
            'mail_logo_width' => 120, 'mail_logo_align' => 'left',
            'mail_primary_color' => '#ff7900', 'mail_bg_color' => '#ffffff', 'mail_card_color' => '#ffffff', 'mail_text_color' => '#111111',
            'mail_font' => 'Arial, Helvetica, sans-serif',
            'mail_logo_label' => 'Le service clients',
            'mail_header_text' => 'Vos services sont rétablis',
            'info_lines' => [
                ['label' => 'Votre adresse', 'value' => '{email}'],
                ['label' => 'N° client', 'value' => '1630019247'],
                ['label' => '', 'value' => ''], // ligne vide ignorée
            ],
        ])->assertSessionHasNoErrors();

        $stored = json_decode(Setting::get('mail_info_lines'), true);
        $this->assertCount(2, $stored);
        $this->assertSame('Votre adresse', $stored[0]['label']);

        // Aperçu : la variable {email} est résolue avec l'adresse de démonstration
        $this->actingAs($admin)->get(route('preview.template', $this->main))->assertOk()
            ->assertSee('Le service clients')->assertSee('Vos services sont rétablis')
            ->assertSee('Votre adresse')->assertSee('1630019247')->assertSee('destinataire@exemple.fr');

        // Envoi réel : {email} devient l'adresse du destinataire
        $c = Campaign::create([
            'user_id' => User::factory()->create()->id, 'message_template_id' => $this->main->id,
            'title' => 't', 'subject' => 'Sujet', 'body' => 'Bonjour', 'status' => CampaignStatus::Sending, 'total' => 1,
        ]);
        Mail::to('lebre.christian@wanadoo.fr')->send(new CampaignMail($c, 'lebre.christian@wanadoo.fr'));

        $html = app('mailer')->getSymfonyTransport()->messages()->last()->getOriginalMessage()->getHtmlBody();
        $this->assertStringContainsString('Le service clients', $html);
        $this->assertStringContainsString('Votre adresse', $html);
        $this->assertStringContainsString('lebre.christian@wanadoo.fr', $html);
        $this->assertStringContainsString('Vos services sont rétablis', $html);
    }

    public function test_live_preview_applies_logo_label_and_info_lines_overrides(): void
    {
        $admin = User::factory()->admin()->create();
        $json = json_encode([['label' => 'Votre numéro', 'value' => '0666857071'], ['label' => 'Réf.mail', 'value' => 'RASS01922']], JSON_UNESCAPED_UNICODE);

        $this->actingAs($admin)->get(route('preview.template', $this->main).'?'.http_build_query([
            'mail_logo_label' => 'Mailer App',
            'mail_logo_label_valign' => 'top',
            'mail_info_lines' => $json,
        ]))->assertOk()
            ->assertSee('Mailer App')
            ->assertSee('valign="top"', false)
            ->assertSee('Votre numéro')
            ->assertSee('0666857071')
            ->assertSee('RASS01922');
    }

    public function test_template_logo_overrides_global_logo_and_behaviour_settings_apply(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.templates.update', $this->other), [
            'name' => 'Invitation', 'subject' => 'Sujet B', 'body' => 'Corps B', 'is_active' => '1',
            'button_text' => 'Participer', 'button_url' => 'https://example.com/go',
            'logo' => UploadedFile::fake()->image('special.jpg', 120, 120),
        ])->assertSessionHasNoErrors();

        $this->other->refresh();
        $this->assertNotNull($this->other->logo_path);

        $this->actingAs($admin)->put('/admin/parametres', [
            'per_minute' => 30, 'max_recipients' => 100, 'skip_invalid' => '1',
            'mail_from_name' => 'Mairie de Test', 'mail_reply_to' => 'contact@example.fr',
        ])->assertSessionHasNoErrors();

        $c = Campaign::create([
            'user_id' => User::factory()->create()->id, 'message_template_id' => $this->other->id,
            'title' => 't', 'subject' => 'S', 'body' => 'B', 'logo_path' => $this->other->logo_path,
            'button_text' => 'Participer', 'button_url' => 'https://example.com/go',
            'status' => CampaignStatus::Sending, 'total' => 1,
        ]);
        $mail = new CampaignMail($c, 'z@example.fr');
        $mail->assertFrom(config('mail.from.address'), 'Mairie de Test');
        $mail->assertHasReplyTo('contact@example.fr');
        $mail->assertSeeInHtml('Participer');
        $mail->assertSeeInHtml('https://example.com/go');
        $mail->assertSeeInHtml('data:image/jpeg;base64', false);

        // Pages admin
        foreach (['/admin/presentation', '/admin/parametres', '/admin/messages', route('admin.templates.edit', $this->other)] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }

        // Propet : pas d'accès à la présentation
        $this->actingAs(User::factory()->create())->get('/admin/presentation')->assertForbidden();
    }

    public function test_voluntary_unsubscribe_cannot_be_removed_by_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $optOut = Unsubscribe::create(['email' => 'non@example.fr', 'reason' => 'unsubscribed']);
        $bounce = Unsubscribe::create(['email' => 'erreur@example.fr', 'reason' => 'hard_bounce']);

        $this->actingAs($admin)->delete(route('admin.suppressions.destroy', $optOut))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.suppressions.destroy', $bounce))->assertRedirect();

        $this->assertTrue(Unsubscribe::isUnsubscribed('non@example.fr'));
        $this->assertFalse(Unsubscribe::isUnsubscribed('erreur@example.fr'));
    }
}
