<?php

namespace App\Http\Controllers\Concerns;

use App\Models\MessageTemplate;
use App\Support\ResponseForm;
use Illuminate\Http\Request;

/** Validation et logo communs aux messages de l'admin et à ceux créés par les agents. */
trait HandlesTemplates
{
    /** @return array<string,mixed> */
    protected function validatedTemplate(Request $request): array
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
        // Champs du formulaire de réponse : libellés, types et options paramétrés par l'auteur
        $data['form'] = ResponseForm::normalize($request->input('form'));

        return $data;
    }

    protected function handleLogo(Request $request, MessageTemplate $template): void
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
