<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTemplates;
use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Support\MailBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TemplateController extends Controller
{
    use HandlesTemplates;

    public function index(): View
    {
        return view('admin.templates.index', [
            'templates' => MessageTemplate::with('owner:id,name')->withCount('campaigns')
                ->orderByRaw('user_id is not null')->orderByDesc('is_default')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.templates.form', ['template' => new MessageTemplate(['is_active' => true]), 'logo' => null, 'prefix' => 'admin']);
    }

    public function store(Request $request): RedirectResponse
    {
        $template = MessageTemplate::create($this->validatedTemplate($request));
        $this->handleLogo($request, $template);

        if ($request->boolean('is_default') || MessageTemplate::shared()->count() === 1) {
            $template->makeDefault();
        }

        return redirect()->route('admin.templates.edit', $template)->with('success', 'Message créé.');
    }

    public function edit(MessageTemplate $template): View
    {
        return view('admin.templates.form', ['template' => $template, 'logo' => MailBranding::dataUri($template->logo_path), 'prefix' => 'admin']);
    }

    public function update(Request $request, MessageTemplate $template): RedirectResponse
    {
        $template->update($this->validatedTemplate($request));
        $this->handleLogo($request, $template);

        // Un message personnel d'agent ne peut pas devenir le message recommandé de tous.
        if ($request->boolean('is_default') && ! $template->isPersonal()) {
            $template->makeDefault();
        }

        return back()->with('success', 'Message mis à jour.');
    }

    public function destroy(MessageTemplate $template): RedirectResponse
    {
        if ($template->campaigns()->exists()) {
            return back()->with('error', 'Ce message est utilisé par des listes : désactivez-le plutôt que de le supprimer.');
        }

        if ($template->logo_path) {
            Storage::disk('local')->delete($template->logo_path);
        }
        $template->delete();

        return back()->with('success', 'Message supprimé.');
    }
}
