<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Support\MailBranding;
use App\Support\ResponseForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.templates.index', ['templates' => MessageTemplate::withCount('campaigns')->orderByDesc('is_default')->latest()->get()]);
    }

    public function create(): View
    {
        return view('admin.templates.form', ['template' => new MessageTemplate(['is_active' => true]), 'logo' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $template = MessageTemplate::create($this->validated($request));
        $this->handleLogo($request, $template);

        if ($request->boolean('is_default') || MessageTemplate::count() === 1) {
            $template->makeDefault();
        }

        return redirect()->route('admin.templates.edit', $template)->with('success', 'Message créé.');
    }

    public function edit(MessageTemplate $template): View
    {
        return view('admin.templates.form', ['template' => $template, 'logo' => MailBranding::dataUri($template->logo_path)]);
    }

    public function update(Request $request, MessageTemplate $template): RedirectResponse
    {
        $template->update($this->validated($request));
        $this->handleLogo($request, $template);

        if ($request->boolean('is_default')) {
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

    /** @return array<string,mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:20000'],
            'button_text' => ['nullable', 'string', 'max:80', 'required_with:button_url'],
            'button_url' => ['nullable', 'url:https,http', 'max:500', 'required_with:button_text'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:1024'],
            'form' => ['nullable', 'array'],
            'form.fields' => ['nullable', 'array', 'max:30'],
        ]);
        unset($data['logo']);
        $data['is_active'] = $request->boolean('is_active');
        // Champs du formulaire de réponse : libellés, types et options paramétrés par l'admin
        $data['form'] = ResponseForm::normalize($request->input('form'));

        return $data;
    }

    private function handleLogo(Request $request, MessageTemplate $template): void
    {
        if ($request->boolean('remove_logo') && $template->logo_path) {
            // Le fichier peut encore servir à des listes déjà soumises : on le conserve.
            $template->update(['logo_path' => null]);
        }

        if ($request->hasFile('logo')) {
            $template->update(['logo_path' => $request->file('logo')->store('logos', 'local')]);
        }
    }
}
