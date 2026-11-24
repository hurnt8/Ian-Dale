<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function templates(): array
    {
        return [
            MessageTemplate::create(['name' => 'Avis', 'subject' => 's', 'body' => 'b', 'is_active' => true, 'is_default' => true]),
            MessageTemplate::create(['name' => 'Invitation', 'subject' => 's', 'body' => 'b', 'is_active' => true]),
            MessageTemplate::create(['name' => 'Relance', 'subject' => 's', 'body' => 'b', 'is_active' => true]),
        ];
    }

    public function test_admin_assigns_allowed_messages_to_an_agent(): void
    {
        $admin = User::factory()->admin()->create();
        $propet = User::factory()->create(['name' => 'Agent A']);
        [$avis, $invit] = $this->templates();

        $this->actingAs($admin)->put(route('admin.users.update', $propet), [
            'name' => 'Agent A', 'email' => $propet->email, 'role' => 'propet', 'is_active' => '1',
            'templates' => [$avis->id, $invit->id],
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing([$avis->id, $invit->id], $propet->allowedTemplates()->pluck('message_templates.id')->all());
    }

    public function test_agent_only_sees_and_can_use_allowed_messages(): void
    {
        Setting::put('require_approval', '0');
        [$avis, $invit, $relance] = $this->templates();
        $propet = User::factory()->create();
        $propet->allowedTemplates()->sync([$avis->id, $invit->id]); // pas "Relance"

        // Le formulaire d'envoi ne propose que les messages autorisés
        $this->actingAs($propet)->get(route('propet.campaigns.create'))->assertOk()
            ->assertSee('Avis')->assertSee('Invitation')->assertDontSee('Relance');

        // Un message non autorisé est refusé
        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'X', 'message_template_id' => $relance->id, 'emails' => 'a@example.fr',
        ])->assertSessionHasErrors('message_template_id');
        $this->assertSame(0, Campaign::count());

        // Un message autorisé passe
        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'OK', 'message_template_id' => $avis->id, 'emails' => 'a@example.fr',
        ])->assertRedirect();
        $this->assertSame(1, Campaign::count());
    }

    public function test_agent_without_assignment_can_use_all_active_messages(): void
    {
        Setting::put('require_approval', '0');
        [$avis, $invit, $relance] = $this->templates();
        $propet = User::factory()->create(); // aucune attribution

        $this->actingAs($propet)->get(route('propet.campaigns.create'))->assertOk()
            ->assertSee('Avis')->assertSee('Invitation')->assertSee('Relance');

        $this->actingAs($propet)->post('/propet/campagnes', [
            'title' => 'OK', 'message_template_id' => $relance->id, 'emails' => 'a@example.fr',
        ])->assertRedirect();
        $this->assertSame(1, Campaign::count());
    }
}
