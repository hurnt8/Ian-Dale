<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Support\MailBranding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Présentation des emails : logo, couleurs, police, en-tête, pied de page. */
class PresentationController extends Controller
{
    public function edit(): View
    {
        return view('admin.presentation', [
            'b' => MailBranding::current(),
            'logo' => MailBranding::dataUri(Setting::get('mail_logo_path')),
            'fonts' => MailBranding::FONTS,
            'templates' => MessageTemplate::orderByDesc('is_default')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $color = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];

        $data = $request->validate([
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:1024'],
            'mail_logo_width' => ['required', 'integer', 'min:40', 'max:600'],
            'mail_logo_align' => ['required', Rule::in(['left', 'center', 'right'])],
            'mail_primary_color' => $color,
            'mail_bg_color' => $color,
            'mail_card_color' => $color,
            'mail_text_color' => $color,
            'mail_font' => ['required', Rule::in(array_keys(MailBranding::FONTS))],
            'mail_header_text' => ['nullable', 'string', 'max:150'],
            'mail_footer_text' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->boolean('remove_logo') && ($old = Setting::get('mail_logo_path'))) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($old);
            Setting::put('mail_logo_path', null);
        }

        if ($request->hasFile('logo')) {
            Setting::put('mail_logo_path', MailBranding::storeLogo($request->file('logo'), Setting::get('mail_logo_path')));
        }

        foreach ($data as $key => $value) {
            if ($key !== 'logo') {
                Setting::put($key, $value === null ? null : (string) $value);
            }
        }

        return back()->with('success', 'Présentation enregistrée.');
    }
}
