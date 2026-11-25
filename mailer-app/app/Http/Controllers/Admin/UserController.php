<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::withCount('campaigns')->orderBy('role')->orderBy('name')->paginate(25)]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['role' => Role::Propet, 'is_active' => true]),
            'templates' => MessageTemplate::available()->get(),
            'assigned' => [],
            'pages' => Page::orderByDesc('id')->get(),
            'assignedPages' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', Password::min(8)],
            'templates' => ['sometimes', 'array'],
            'templates.*' => ['integer', Rule::exists('message_templates', 'id')],
            'pages' => ['sometimes', 'array'],
            'pages.*' => ['integer', Rule::exists('pages', 'id')],
        ]);

        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'], 'password' => $data['password'], 'is_active' => true]);
        $user->allowedTemplates()->sync($data['templates'] ?? []);
        $user->assignedPages()->sync($data['pages'] ?? []);

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'templates' => MessageTemplate::available()->get(),
            'assigned' => $user->allowedTemplates()->pluck('message_templates.id')->all(),
            'pages' => Page::orderByDesc('id')->get(),
            'assignedPages' => $user->assignedPages()->pluck('pages.id')->all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['nullable', Password::min(8)],
            'is_active' => ['sometimes', 'boolean'],
            'templates' => ['sometimes', 'array'],
            'templates.*' => ['integer', Rule::exists('message_templates', 'id')],
            'pages' => ['sometimes', 'array'],
            'pages.*' => ['integer', Rule::exists('pages', 'id')],
        ]);

        if ($user->is($request->user()) && ($data['role'] !== Role::Admin->value || ! $request->boolean('is_active'))) {
            return back()->with('error', 'Vous ne pouvez pas retirer vos propres droits admin ni désactiver votre compte.');
        }

        $templates = $data['templates'] ?? [];
        $pages = $data['pages'] ?? [];
        unset($data['templates'], $data['pages']);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $data['is_active'] = $request->boolean('is_active');

        $user->update($data);
        $user->allowedTemplates()->sync($templates);
        $user->assignedPages()->sync($pages);

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return back()->with('success', 'Utilisateur supprimé.');
    }
}
