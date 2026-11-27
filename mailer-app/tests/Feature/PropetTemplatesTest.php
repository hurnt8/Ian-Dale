<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\MessageTemplate;
use App\Models\Response;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropetTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Mon avis', 'subject' => 'Votre avis', 'body' => 'Bonjour {email} {lien}', 'is_active' => '1',
            'form' => ['enabled' => '1', 'title' => 'Donnez votre avis', 'fields' => [
                ['label' => 'Votre note', 'type' => 'rating', 'required' => '1'],
            ]],
        ], $extra);
    }

    public function test_propet_creates_own_message_with_form_and_can_use_it(): void
    {
        $propet = User::factory()->create();

        $this->actingAs($propet)->get(route('propet.templates.create'))->assertOk();
        $this->actingAs($propet)->post(route('propet.templates.store'), $this->payload())->assertRedirect();

        $template = MessageTemplate::firstOrFail();
        $this->assertSame($propet->id, $template->user_id);
        $this->assertTrue($template->hasForm());

        $this->actingAs($propet)->get(route('propet.templates.index'))->assertOk()->assertSee('Mon avis');
        $this->actingAs($propet)->get(route('preview.form', $template))->assertOk();
        $this->assertContains($template->id, $propet->availableTemplates()->pluck('id')->all());
    }

    public function test_propet_can_remove_orange_bar_or_change_its_color(): void
    {
        $propet = User::factory()->create();

        // Par défaut : bandeau orange conservé
        $this->actingAs($propet)->post(route('propet.templates.store'), $this->payload());
        $template = MessageTemplate::firstOrFail();
        $this->actingAs($propet)->get(route('preview.form', $template))->assertOk()
            ->assertSee('class="pub-bar"', false)->assertSee('--pub-accent: #ff7900', false);

        // Sans bandeau, autre couleur
        $payload = $this->payload();
        $payload['form'] += ['show_bar' => '0', 'accent_color' => '#1E88E5'];
        $this->actingAs($propet)->put(route('propet.templates.update', $template), $payload);
        $this->actingAs($propet)->get(route('preview.form', $template))->assertOk()
            ->assertDontSee('class="pub-bar"', false)->assertSee('--pub-accent: #1e88e5', false);

        // Couleur invalide : retour à l'orange
        $payload['form']['accent_color'] = 'red;}body{display:none';
        $this->actingAs($propet)->put(route('propet.templates.update', $template), $payload);
        $this->assertSame('#ff7900', $template->fresh()->form['accent_color']);
    }

    public function test_personal_messages_stay_private_to_their_author(): void
    {
        MessageTemplate::create(['name' => 'Partagé', 'subject' => 's', 'body' => 'b', 'is_active' => true]);
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $private = $alice->ownTemplates()->create(['name' => 'Privé Alice', 'subject' => 's', 'body' => 'b', 'is_active' => true]);

        $this->assertNotContains($private->id, $bob->availableTemplates()->pluck('id')->all());
        $this->actingAs($bob)->get(route('propet.templates.edit', $private))->assertNotFound();
        $this->actingAs($bob)->put(route('propet.templates.update', $private), $this->payload())->assertNotFound();
        $this->actingAs($bob)->get(route('preview.template', $private))->assertNotFound();
        $this->actingAs($bob)->get(route('propet.campaigns.create'))->assertDontSee('Privé Alice');

        // Alice voit à la fois le message partagé et le sien
        $this->assertEqualsCanonicalizing(['Partagé', 'Privé Alice'], $alice->availableTemplates()->pluck('name')->all());
    }

    public function test_propet_sees_responses_of_their_message(): void
    {
        $propet = User::factory()->create();
        $template = $propet->ownTemplates()->create(['name' => 'Mon avis', 'subject' => 's', 'body' => 'b', 'is_active' => true]);
        $campaign = Campaign::create(['user_id' => $propet->id, 'message_template_id' => $template->id, 'title' => 'L1', 'subject' => 's', 'body' => 'b', 'form' => ['enabled' => true], 'status' => 'completed', 'total' => 1]);
        $recipient = $campaign->recipients()->create(['email' => 'client@example.fr', 'status' => 'sent']);
        Response::create(['campaign_id' => $campaign->id, 'campaign_recipient_id' => $recipient->id, 'email' => 'client@example.fr',
            'answers' => [['key' => 'note', 'label' => 'Votre note', 'type' => 'rating', 'value' => 4]]]);

        $this->actingAs($propet)->get(route('propet.templates.index'))->assertOk()->assertSee('Réponses');
        $this->actingAs($propet)->get(route('propet.responses.index', ['template' => $template->id]))
            ->assertOk()->assertSee('client@example.fr')->assertSee('Votre note');
    }
}
