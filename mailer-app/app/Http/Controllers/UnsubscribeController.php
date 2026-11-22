<?php

namespace App\Http\Controllers;

use App\Models\Unsubscribe;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class UnsubscribeController extends Controller
{
    public function show(Request $request): View
    {
        return view('unsubscribe', ['email' => (string) $request->query('email'), 'done' => false]);
    }

    /** Clic sur "confirmer" ou désinscription en un clic (RFC 8058) depuis le client mail. */
    public function store(Request $request): View|Response
    {
        $email = mb_strtolower(trim((string) $request->query('email')));
        Unsubscribe::firstOrCreate(['email' => $email]);

        if ($request->has('List-Unsubscribe')) {
            return response()->noContent();
        }

        return view('unsubscribe', ['email' => $email, 'done' => true]);
    }
}
