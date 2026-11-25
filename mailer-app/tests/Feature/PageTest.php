<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageResponse;
use App\Models\User;
use App\Support\ResponseForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPage(): Page
    {
        return Page::create([
            'slug' => 'inscription',
            'is_published' => true,
            'form' => ResponseForm::normalize([
                'enabled' => true,
                'title' => 'Inscription',
                'submit_label' => 'Je m’inscris',
                'thanks' => 'Inscription enregistrée',
                'fields' => [
                    ['label' => 'Votre nom', 'type' => 'text', 'required' => true],
                    ['label' => 'Votre note', 'type' => 'rating', 'required' => true],
                ],
            ]),
        ]);
    }

    public function test_admin_creates_a_view_with_a_public_address(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.pages.store'), [
            'is_published' => '1',
            'logo_align' => 'left',
            'form' => [
                'title' => 'Contact rapide',
                'submit_label' => 'Envoyer',
                'fields' => [
                    ['label' => 'Votre message', 'type' => 'textarea', 'required' => '1'],
                    ['label' => 'Choix vide', 'type' => 'select', 'options' => ''], // ignoré
                ],
            ],
        ])->assertRedirect();

        $page = Page::firstOrFail();
        $this->assertSame('contact-rapide', $page->slug);
        $this->assertTrue($page->is_published);
        $this->assertSame('left', $page->form['logo_align']); // position du logo enregistrée
        $this->assertSame(['votre_message'], array_column($page->form['fields'], 'key'));
    }

    public function test_public_can_open_a_published_view_but_not_a_draft(): void
    {
        $page = $this->publishedPage();

        $this->get(route('page.show', $page->slug))->assertOk()
            ->assertSee('Inscription')->assertSee('Votre nom');

        $page->update(['is_published' => false]);
        $this->get(route('page.show', $page->slug))->assertNotFound();

        $this->get('/vue/adresse-inexistante')->assertNotFound();
    }

    public function test_public_submission_is_validated_and_stored(): void
    {
        $page = $this->publishedPage();

        // Champs obligatoires manquants
        $this->post(route('page.store', $page->slug), ['answers' => []])
            ->assertSessionHasErrors(['answers.votre_nom', 'answers.votre_note']);

        $this->post(route('page.store', $page->slug), [
            'answers' => ['votre_nom' => 'Alice', 'votre_note' => 5],
        ])->assertRedirect(route('page.show', $page->slug));

        $this->assertSame(1, PageResponse::count());
        $resp = PageResponse::firstOrFail();
        $this->assertSame('Votre nom', $resp->answers[0]['label']);
        $this->assertSame('Alice', $resp->answers[0]['value']);

        // La page remercie après l'envoi
        $this->get(route('page.show', $page->slug))->assertSee('Inscription enregistrée');
    }

    public function test_admin_sees_responses_and_can_export(): void
    {
        $admin = User::factory()->admin()->create();
        $page = $this->publishedPage();
        $this->post(route('page.store', $page->slug), ['answers' => ['votre_nom' => 'Bob', 'votre_note' => 4]]);

        $this->actingAs($admin)->get(route('admin.pages.responses', $page))->assertOk()->assertSee('Bob');
        $this->actingAs($admin)->get(route('admin.pages.index'))->assertOk()->assertSee('/vue/inscription');

        $csv = $this->actingAs($admin)->get(route('admin.pages.responses.export', $page))->streamedContent();
        $this->assertStringContainsString('Bob', $csv);
        $this->assertStringContainsString('Votre nom', $csv);

        // Aperçu réservé à l'admin
        $this->actingAs($admin)->get(route('preview.page', $page))->assertOk()->assertSee('Votre nom');
    }

    public function test_a_view_is_reserved_to_admins(): void
    {
        $propet = User::factory()->create();

        $this->actingAs($propet)->get(route('admin.pages.index'))->assertForbidden();
        $this->actingAs($propet)->get(route('admin.pages.create'))->assertForbidden();
    }

    public function test_assigned_agent_sees_view_responses_others_do_not(): void
    {
        $admin = User::factory()->admin()->create();
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        $page = $this->publishedPage();

        // L'admin attribue la vue à Alice depuis l'écran d'édition
        $this->actingAs($admin)->put(route('admin.pages.update', $page), [
            'form' => $page->form,
            'is_published' => 1,
            'agents' => [$alice->id, $admin->id], // un admin coché est ignoré
        ])->assertRedirect();
        $this->assertSame([$alice->id], $page->agents()->pluck('users.id')->all());

        $this->post(route('page.store', $page->slug), ['answers' => ['votre_nom' => 'Zoé', 'votre_note' => 5]]);

        $this->actingAs($alice)->get(route('propet.pages.index'))->assertOk()->assertSee('Inscription');
        $this->actingAs($alice)->get(route('propet.pages.responses', $page))->assertOk()->assertSee('Zoé');
        $csv = $this->actingAs($alice)->get(route('propet.pages.responses.export', $page))->streamedContent();
        $this->assertStringContainsString('Zoé', $csv);

        $this->actingAs($bob)->get(route('propet.pages.index'))->assertOk()->assertDontSee('Inscription');
        $this->actingAs($bob)->get(route('propet.pages.responses', $page))->assertNotFound();
        $this->actingAs($bob)->get(route('propet.pages.responses.export', $page))->assertNotFound();
    }

    public function test_admin_assigns_views_from_the_agent_screen(): void
    {
        $admin = User::factory()->admin()->create();
        $alice = User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.fr']);
        $page = $this->publishedPage();

        $this->actingAs($admin)->get(route('admin.users.edit', $alice))->assertOk()
            ->assertSee('Vues dont cet agent voit les réponses')->assertSee('/vue/inscription');

        $this->actingAs($admin)->put(route('admin.users.update', $alice), [
            'name' => 'Alice', 'email' => 'alice@example.fr', 'role' => 'propet', 'is_active' => 1,
            'pages' => [$page->id],
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame([$page->id], $alice->assignedPages()->pluck('pages.id')->all());
        $this->actingAs($alice)->get(route('propet.pages.responses', $page))->assertOk();
    }

    public function test_thanks_screen_has_no_back_button(): void
    {
        $page = $this->publishedPage();

        $this->post(route('page.store', $page->slug), ['answers' => ['votre_nom' => 'Alice', 'votre_note' => 5]]);
        $this->get(route('page.show', $page->slug))->assertSee('Inscription enregistrée')->assertDontSee('Retour');
    }

    public function test_builder_screens_render(): void
    {
        $admin = User::factory()->admin()->create();
        $page = $this->publishedPage();

        $this->actingAs($admin)->get(route('admin.pages.create'))->assertOk()->assertSee('Titre de la vue');
        $this->actingAs($admin)->get(route('admin.pages.edit', $page))->assertOk()
            ->assertSee('/vue/inscription')->assertSee('Aperçu de la vue');
    }
}
