<?php

namespace App\Http\Controllers;

use App\Enums\VerificationStatus;
use App\Models\Setting;
use App\Models\Unsubscribe;
use App\Services\Verification\EmailVerifier;
use App\Services\Verification\VerificationResult;
use App\Support\EmailListParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Outil de vérification des adresses (syntaxe, domaine, serveur mail, fautes de frappe). */
class VerificationController extends Controller
{
    public function __construct(private EmailVerifier $verifier) {}

    public function show(): View
    {
        return view('verification.index', ['max' => Setting::int('max_recipients')]);
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate(['emails' => ['required', 'string', 'max:200000']]);

        $parsed = EmailListParser::parse($data['emails']);
        $max = Setting::int('max_recipients');

        if (count($parsed['valid']) > $max) {
            return response()->json(['message' => "La liste dépasse la limite de $max adresses."], 422);
        }

        $results = array_merge(
            array_map(fn (string $raw) => new VerificationResult($raw, VerificationStatus::InvalidSyntax, "Format d'adresse incorrect."), $parsed['invalid']),
            $this->verifier->verifyMany($parsed['valid']),
        );

        $suppressed = Unsubscribe::query()->whereIn('email', $parsed['valid'])->pluck('reason', 'email');

        $rows = array_map(function (VerificationResult $r) use ($suppressed) {
            $row = $r->toArray();
            $row['suppressed'] = $suppressed[$r->email] ?? null; // désinscrit ou boîte inexistante connue
            if ($row['suppressed'] === 'hard_bounce') {
                $row = [...$row, 'status' => VerificationStatus::MailboxNotFound->value, 'label' => VerificationStatus::MailboxNotFound->label(),
                    'color' => VerificationStatus::MailboxNotFound->color(), 'blocking' => true, 'detail' => 'Déjà rejetée par le serveur lors d\'un envoi précédent.'];
            }

            return $row;
        }, $results);

        $summary = collect($rows)->countBy('status')->all();

        return response()->json([
            'results' => $rows,
            'summary' => $summary,
            'duplicates' => $parsed['duplicates'],
            'total' => count($rows),
            'sendable' => collect($rows)->where('blocking', false)->whereNull('suppressed')->count(),
        ]);
    }
}
