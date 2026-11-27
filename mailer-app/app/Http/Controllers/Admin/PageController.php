<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\User;
use App\Support\MailBranding;
use App\Support\PageResponsesExport;
use App\Support\ResponseForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gestion des « vues » : pages publiques autonomes paramétrées par l'admin.
 */
class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::withCount('responses')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.form', $this->formData(new Page(['is_published' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $page = Page::create($this->validated($request));
        $this->syncAgents($request, $page);
        $this->handleLogo($request, $page);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Vue créée.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', $this->formData($page));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));
        $this->syncAgents($request, $page);
        $this->handleLogo($request, $page);

        return back()->with('success', 'Vue mise à jour.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->logo_path) {
            Storage::disk('local')->delete($page->logo_path);
        }
        $page->delete(); // réponses supprimées en cascade

        return redirect()->route('admin.pages.index')->with('success', 'Vue supprimée.');
    }

    public function responses(Page $page): View
    {
        return view('admin.pages.responses', [
            'page' => $page,
            'responses' => $page->responses()->with('agent:id,name')->latest()->paginate(40),
        ]);
    }

    public function exportResponses(Page $page): StreamedResponse
    {
        return PageResponsesExport::download($page);
    }

    /** Données communes au formulaire de création / modification. */
    private function formData(Page $page): array
    {
        return [
            'page' => $page,
            'logo' => MailBranding::dataUri($page->logo_path),
            'propets' => User::where('role', Role::Propet->value)->orderBy('name')->get(['id', 'name', 'email']),
            'assigned' => $page->exists ? $page->agents()->pluck('users.id')->all() : [],
        ];
    }

    /** Enregistre les agents autorisés à voir les réponses (seuls des propets). */
    private function syncAgents(Request $request, Page $page): void
    {
        $ids = User::where('role', Role::Propet->value)
            ->whereIn('id', array_map('intval', (array) $request->input('agents', [])))
            ->pluck('id');

        $page->agents()->sync($ids);
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, ?Page $page = null): array
    {
        $request->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:1024'],
            'form' => ['nullable', 'array'],
            'form.fields' => ['nullable', 'array', 'max:30'],
            'agents' => ['sometimes', 'array'],
            'agents.*' => ['integer'],
        ]);

        $form = ResponseForm::normalize($request->input('form'));
        // Une vue publique n'a de sens que si elle est activée et possède des champs.
        $form['enabled'] = true;
        // Position du logo propre à la vue (gauche / centré / droite).
        $align = $request->input('logo_align');
        $form['logo_align'] = in_array($align, ['left', 'center', 'right'], true) ? $align : 'center';

        return [
            'form' => $form,
            'is_published' => $request->boolean('is_published'),
            'slug' => $this->uniqueSlug($form['title'], $page),
        ];
    }

    /** Génère un slug lisible et unique à partir du titre, en conservant celui de la vue existante si possible. */
    private function uniqueSlug(string $title, ?Page $page): string
    {
        $base = Str::slug($title) ?: 'vue';

        // On garde le slug actuel s'il dérive déjà du même titre (évite de casser les liens partagés).
        if ($page && $page->exists && Str::startsWith($page->slug, $base)) {
            return $page->slug;
        }

        $slug = $base;
        for ($i = 2; Page::where('slug', $slug)->when($page, fn ($q) => $q->whereKeyNot($page->getKey()))->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    private function handleLogo(Request $request, Page $page): void
    {
        if ($request->boolean('remove_logo') && $page->logo_path) {
            Storage::disk('local')->delete($page->logo_path);
            $page->update(['logo_path' => null]);
        }

        if ($request->hasFile('logo')) {
            $page->update(['logo_path' => $request->file('logo')->store('logos', 'local')]);
        }
    }
}
