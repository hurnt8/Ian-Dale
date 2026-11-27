<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageResponse;
use App\Models\User;
use App\Support\AgentLink;
use App\Support\ResponseForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Lien personnel d'un agent sur une vue : la réponse revient à l'agent qui a partagé le lien. */
class AgentPageLinkTest extends TestCase
{
    use RefreshDatabase;

    private function page(): Page
    {
        return Page::create(['slug' => 'mon-avis', 'is_published' => true, 'form' => ResponseForm::normalize([
            'enabled' => true, 'title' => 'Mon avis', 'fields' => [['label' => 'Votre message', 'type' => 'textarea', 'required' => true]],
        ])]);
    }

    public function test_answer_through_agent_link_goes_back_to_that_agent_only(): void
    {
        $page = $this->page();
        $alice = User::factory()->create(['name' => 'Alice']);
        $bob = User::factory()->create(['name' => 'Bob']);
        $page->agents()->sync([$alice->id, $bob->id]); // vue attribuée aux deux agents
        $key = $page->form['fields'][0]['key'];

        // Le client ouvre le lien d'Alice et répond
        $link = $page->linkFor($alice);
        $this->get($link)->assertOk()->assertSee(AgentLink::code($alice), false);
        $this->post(route('page.store', ['slug' => 'mon-avis', 'a' => AgentLink::code($alice)]), ['answers' => [$key => 'Merci Alice !']])
            ->assertRedirect(route('page.show', ['slug' => 'mon-avis', 'a' => AgentLink::code($alice)]));

        $this->assertSame($alice->id, PageResponse::firstOrFail()->user_id);

        // Alice la voit (liste des vues, page des réponses, page « Réponses reçues »), Bob non
        $this->actingAs($alice)->get(route('propet.pages.index'))->assertOk()->assertSee($link, false);
        $this->actingAs($alice)->get(route('propet.pages.responses', $page))->assertOk()->assertSee('Merci Alice !');
        $this->actingAs($alice)->get(route('propet.responses.index'))->assertOk()->assertSee('Merci Alice !');
        $this->assertStringContainsString('Merci Alice !', $this->actingAs($alice)->get(route('propet.pages.responses.export', $page))->streamedContent());
        $this->actingAs($bob)->get(route('propet.pages.responses', $page))->assertOk()->assertDontSee('Merci Alice !');
        $this->actingAs($bob)->get(route('propet.responses.index'))->assertDontSee('Merci Alice !');
        $this->assertStringNotContainsString('Merci Alice !', $this->actingAs($bob)->get(route('propet.pages.responses.export', $page))->streamedContent());

        // L'admin voit tout, avec le nom de l'agent
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.pages.responses', $page))
            ->assertOk()->assertSee('Merci Alice !')->assertSee('Alice');
    }

    public function test_forged_agent_code_is_ignored(): void
    {
        $page = $this->page();
        $alice = User::factory()->create();
        $page->agents()->sync([$alice->id]);
        $key = $page->form['fields'][0]['key'];

        $this->post(route('page.store', ['slug' => 'mon-avis', 'a' => $alice->id.'-0000000000']), ['answers' => [$key => 'Faux']]);

        $this->assertNull(PageResponse::firstOrFail()->user_id);
    }
}
