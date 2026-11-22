<?php

namespace App\Http\Controllers;

use App\Enums\RecipientStatus;
use App\Enums\Role;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Liste des adresses email.
 *  - propet : uniquement les adresses de SES listes
 *  - admin  : la totalité, avec filtre par propet et export CSV
 */
class RecipientController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = $this->query($request);

        return view('recipients.index', [
            'recipients' => (clone $query)->with('campaign.user')->latest('id')->paginate(50)->withQueryString(),
            'totals' => $this->totals($this->query($request, withStatus: false)),
            'statuses' => RecipientStatus::cases(),
            'campaigns' => $user->isAdmin()
                ? \App\Models\Campaign::orderByDesc('id')->get(['id', 'title'])
                : $user->campaigns()->orderByDesc('id')->get(['id', 'title']),
            'propets' => $user->isAdmin()
                ? User::where('role', Role::Propet->value)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $query = $this->query($request)->with('campaign.user')->orderBy('id');

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM pour Excel
            fputcsv($out, ['Email', 'Statut', 'Liste', 'Propet', 'Ajouté le', 'Envoyé le', 'Erreur'], ';');

            $query->chunk(500, function ($rows) use ($out): void {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->email,
                        $r->status->label(),
                        $r->campaign->title,
                        $r->campaign->user->name,
                        $r->created_at->format('d/m/Y H:i'),
                        $r->sent_at?->format('d/m/Y H:i'),
                        $r->error,
                    ], ';');
                }
            });

            fclose($out);
        }, 'emails-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Requête filtrée, TOUJOURS limitée au propet connecté s'il n'est pas admin. */
    private function query(Request $request, bool $withStatus = true): Builder
    {
        $user = $request->user();

        return CampaignRecipient::query()
            ->when(! $user->isAdmin(), fn (Builder $q) => $q->whereHas('campaign', fn (Builder $c) => $c->where('user_id', $user->id)))
            ->when($user->isAdmin() && $request->filled('propet'), fn (Builder $q) => $q->whereHas('campaign', fn (Builder $c) => $c->where('user_id', $request->integer('propet'))))
            ->when($request->filled('campaign'), fn (Builder $q) => $q->where('campaign_id', $request->integer('campaign')))
            ->when($request->filled('q'), fn (Builder $q) => $q->where('email', 'like', '%'.mb_strtolower(trim((string) $request->query('q'))).'%'))
            ->when($withStatus && $request->filled('status'), fn (Builder $q) => $q->where('status', (string) $request->query('status')));
    }

    /** @return array<string,int> */
    private function totals(Builder $query): array
    {
        $counts = $query->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return collect(RecipientStatus::cases())
            ->mapWithKeys(fn (RecipientStatus $s) => [$s->value => (int) ($counts[$s->value] ?? 0)])
            ->put('all', (int) $counts->sum())
            ->all();
    }
}
