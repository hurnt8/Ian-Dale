<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Campaign;
use App\Models\MessageTemplate;
use App\Models\PageResponse;
use App\Models\Response;
use App\Models\User;
use App\Support\ResponseForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Réponses reçues via le formulaire de l'email.
 *  - propet : uniquement les réponses des destinataires de SES listes
 *  - admin  : toutes, filtrables par propet
 */
class ResponseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $base = $this->query($request);

        $campaignsWithForm = Campaign::query()
            ->whereNotNull('form')
            ->when(! $user->isAdmin(), fn (Builder $q) => $q->where('user_id', $user->id))
            ->withCount(['responses', 'recipients as sent_count' => fn ($q) => $q->where('status', 'sent')])
            ->orderByDesc('id')
            ->get(['id', 'title', 'user_id']);

        return view('responses.index', [
            'responses' => (clone $base)->with('campaign.user')->latest('updated_at')->paginate(20)->withQueryString(),
            'total' => (clone $base)->count(),
            'campaigns' => $campaignsWithForm,
            'propets' => $user->isAdmin() ? User::where('role', Role::Propet->value)->orderBy('name')->get(['id', 'name']) : collect(),
            'summary' => $request->filled('campaign') || $request->filled('template') ? $this->summary((clone $base)->get()) : null,
            'templates' => $this->templatesWithResponses($user),
            'isAdmin' => $user->isAdmin(),
            'pageResponses' => $request->hasAny(['campaign', 'template', 'propet', 'q']) ? collect() : $this->pageResponses($user),
        ]);
    }

    /**
     * Messages ayant servi à des listes avec formulaire (les siens seulement pour un agent).
     *
     * @return \Illuminate\Support\Collection<int,MessageTemplate>
     */
    private function templatesWithResponses(User $user)
    {
        return MessageTemplate::query()
            ->whereHas('campaigns', fn (Builder $c) => $c->whereNotNull('campaigns.form')->when(! $user->isAdmin(), fn (Builder $q) => $q->where('campaigns.user_id', $user->id)))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Dernières réponses déposées sur les vues (pages /vue/…) :
     * celles attribuées à l'agent, ou toutes pour l'admin.
     *
     * @return \Illuminate\Support\Collection<int,PageResponse>
     */
    private function pageResponses(User $user)
    {
        return PageResponse::query()
            ->visibleTo($user) // agent : réponses de son lien + lien général des vues attribuées
            ->with('page')
            ->latest()
            ->limit(30)
            ->get();
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = $this->query($request)->with('campaign.user')->orderBy('id')->get();

        // Colonnes = union des libellés de champs rencontrés
        $labels = $rows->flatMap(fn (Response $r) => collect($r->answers)->pluck('label'))->unique()->values()->all();

        return response()->streamDownload(function () use ($rows, $labels): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Email', 'Liste', 'Propet', 'Répondu le', ...$labels], ';');
            foreach ($rows as $r) {
                $byLabel = collect($r->answers)->keyBy('label');
                fputcsv($out, [
                    $r->email, $r->campaign->title, $r->campaign->user->name, $r->updated_at->format('d/m/Y H:i'),
                    ...array_map(fn ($l) => isset($byLabel[$l]) ? ResponseForm::display($byLabel[$l]) : '', $labels),
                ], ';');
            }
            fclose($out);
        }, 'reponses-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Requête TOUJOURS limitée aux listes du propet connecté s'il n'est pas admin. */
    private function query(Request $request): Builder
    {
        $user = $request->user();

        return Response::query()
            ->visibleTo($user)
            ->when($user->isAdmin() && $request->filled('propet'), fn (Builder $q) => $q->whereHas('campaign', fn ($c) => $c->where('user_id', $request->integer('propet'))))
            ->when($request->filled('campaign'), fn (Builder $q) => $q->where('campaign_id', $request->integer('campaign')))
            ->when($request->filled('template'), fn (Builder $q) => $q->whereHas('campaign', fn ($c) => $c->where('message_template_id', $request->integer('template'))))
            ->when($request->filled('q'), fn (Builder $q) => $q->where('email', 'like', '%'.mb_strtolower(trim((string) $request->query('q'))).'%'));
    }

    /**
     * Synthèse par champ (moyenne des notes, répartition des choix) pour une liste.
     *
     * @param  \Illuminate\Support\Collection<int,Response>  $responses
     * @return list<array<string,mixed>>
     */
    private function summary($responses): array
    {
        $fields = [];
        foreach ($responses as $r) {
            foreach ($r->answers as $a) {
                $fields[$a['key']] ??= ['label' => $a['label'], 'type' => $a['type'], 'values' => []];
                if ($a['value'] !== null && $a['value'] !== '' && $a['value'] !== []) {
                    $fields[$a['key']]['values'][] = $a['value'];
                }
            }
        }

        return array_values(array_map(function ($f) {
            $vals = $f['values'];
            $f['count'] = count($vals);
            if ($f['type'] === 'rating' || $f['type'] === 'number') {
                $f['average'] = $vals ? round(array_sum(array_map('floatval', $vals)) / count($vals), 1) : null;
            }
            if (in_array($f['type'], ['radio', 'select', 'checkbox', 'yesno', 'rating'], true)) {
                $flat = collect($vals)->flatten()->map(fn ($v) => (string) $v);
                $f['distribution'] = $flat->countBy()->sortDesc()->all();
            }
            unset($f['values']);

            return $f;
        }, $fields));
    }
}
