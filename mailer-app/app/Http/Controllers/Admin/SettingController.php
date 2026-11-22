<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Unsubscribe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Comportement de l'envoi automatique. */
class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'requireApproval' => Setting::bool('require_approval'),
            'skipInvalid' => Setting::bool('skip_invalid'),
            'skipTypo' => Setting::bool('skip_typo'),
            'orangeOnly' => Setting::bool('orange_only'),
            'orangeDomains' => Setting::get('orange_domains'),
            'perMinute' => Setting::int('per_minute'),
            'maxRecipients' => Setting::int('max_recipients'),
            'fromName' => Setting::get('mail_from_name'),
            'replyTo' => Setting::get('mail_reply_to'),
            'unsubscribes' => Unsubscribe::latest()->paginate(30),
            'mailer' => config('mail.default'),
            'from' => config('mail.from.address'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'per_minute' => ['required', 'integer', 'min:1', 'max:600'],
            'max_recipients' => ['required', 'integer', 'min:1', 'max:10000'],
            'mail_from_name' => ['nullable', 'string', 'max:100'],
            'mail_reply_to' => ['nullable', 'email', 'max:190'],
            'orange_domains' => ['nullable', 'string', 'max:500'],
        ]);

        foreach (['require_approval', 'skip_invalid', 'skip_typo', 'orange_only'] as $flag) {
            Setting::put($flag, $request->boolean($flag) ? '1' : '0');
        }
        foreach ($data as $key => $value) {
            Setting::put($key, $value === null ? null : (string) $value);
        }

        return back()->with('success', 'Paramètres enregistrés.');
    }

    public function removeSuppression(Unsubscribe $unsubscribe): RedirectResponse
    {
        // Une désinscription volontaire ne peut pas être annulée par l'admin (droit d'opposition).
        abort_unless($unsubscribe->reason === 'hard_bounce', 403, 'Seules les adresses rejetées par erreur peuvent être réactivées.');
        $unsubscribe->delete();

        return back()->with('success', "{$unsubscribe->email} retirée de la liste de suppression.");
    }
}
