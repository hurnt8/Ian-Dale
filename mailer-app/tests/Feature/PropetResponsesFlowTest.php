<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Parcours complet : le propet envoie, le client répond, le propet voit le message du client. */
class PropetResponsesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sender_propet_sees_client_answers(): void
    {
        Mail::fake();
        Setting::put('require_approval', '0');
        Setting::put('orange_only', '0');
        MessageTemplate::create(['name' => 'Admin', 'subject' => 's', 'body' => 'b', 'is_active' => true, 'is_default' => true]);
        $propet = User::factory()->create();

        $this->actingAs($propet)->post(route('propet.templates.store'), [
            'name' => 'Mon avis', 'subject' => 'Votre avis', 'body' => 'Bonjour {lien}', 'is_active' => '1',
            'form' => ['enabled' => '1', 'title' => 'Avis', 'fields' => [
                ['label' => 'Votre message', 'type' => 'textarea', 'required' => '1'],
            ]],
        ]);
        $template = MessageTemplate::where('name', 'Mon avis')->firstOrFail();

        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'Ma liste', 'message_template_id' => $template->id, 'emails' => 'client@orange.fr',
        ])->assertSessionHasNoErrors();

        $campaign = Campaign::firstOrFail();
        $recipient = $campaign->recipients()->firstOrFail();
        $this->assertTrue($campaign->hasForm());

        $key = $campaign->form['fields'][0]['key'];
        auth()->logout();
        $this->post(route('form.store', $recipient->token), ['answers' => [$key => 'Bonjour, je suis très satisfait']])
            ->assertSessionHasNoErrors();

        $this->actingAs($propet)->get(route('propet.responses.index'))->assertOk()->assertSee('Bonjour, je suis très satisfait');
        $this->actingAs($propet)->get(route('propet.responses.index', ['campaign' => $campaign->id]))->assertOk()->assertSee('Bonjour, je suis très satisfait');
        $this->actingAs($propet)->get(route('propet.campaigns.show', $campaign))->assertOk()->assertSee('Répondu')
            ->assertSee('Réponses des clients')->assertSee('Bonjour, je suis très satisfait')->assertSee('Votre message');
    }
}
