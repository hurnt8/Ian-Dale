<?php

namespace App\Http\Controllers\Propet;

use App\Http\Controllers\Concerns\HandlesTemplates;
use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Support\MailBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Messages personnels d'un agent : il crée ses propres modèles (texte + formulaire de réponse),
 * les utilise pour ses listes et consulte les réponses reçues.
 */
class TemplateController extends Controller
{
    use HandlesTemplates;

    public function index(Request $request): View
    {
        return view('propet.templates.index', [
            'templates' => $request->user()->ownTemplates()->withCount(['campaigns', 'responses'])->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.templates.form', ['template' => new MessageTemplate(['is_active' => true]), 'logo' => null, 'prefix' => 'propet']);
    }

    public function store(Request $request): RedirectResponse
    {
        $template = $request->user()->ownTemplates()->create($this->validatedTemplate($request));
        $this->handleLogo($request, $template);

        return redirect()->route('propet.templates.edit', $template)->with('success', 'Message créé. Vous pouvez maintenant le choisir lors de l\'envoi d\'une liste.');
    }

    public function edit(Request $request, MessageTemplate $template): View
    {
        $this->authorizeOwner($request, $template);

        return view('admin.templates.form', ['template' => $template, 'logo' => MailBranding::dataUri($template->logo_path), 'prefix' => 'propet']);
    }

    public function update(Request $request, MessageTemplate $template): RedirectResponse
    {
        $this->authorizeOwner($request, $template);

        $template->update($this->validatedTemplate($request));
        $this->handleLogo($request, $template);

        return back()->with('success', 'Message mis à jour.');
    }

    public function destroy(Request $request, MessageTemplate $template): RedirectResponse
    {
        $this->authorizeOwner($request, $template);

        if ($template->campaigns()->exists()) {
            return back()->with('error', 'Ce message est utilisé par vos listes : désactivez-le plutôt que de le supprimer.');
        }

        if ($template->logo_path) {
            Storage::disk('local')->delete($template->logo_path);
        }
        $template->delete();

        return back()->with('success', 'Message supprimé.');
    }

    /** Un agent ne peut modifier que ses propres messages (404 pour ne rien révéler des autres). */
    private function authorizeOwner(Request $request, MessageTemplate $template): void
    {
        abort_unless($template->user_id === $request->user()->id, 404);
    }
}
