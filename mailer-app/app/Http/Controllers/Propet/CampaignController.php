<?php

namespace App\Http\Controllers\Propet;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\MessageTemplate;
use App\Models\Setting;
use App\Services\CampaignService;
use App\Support\EmailListParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(private CampaignService $service) {}

    public function index(Request $request): View
    {
        $campaigns = $request->user()->campaigns()->with('template:id,name')->latest()->paginate(15);

        return view('propet.campaigns.index', compact('campaigns'));
    }

    public function create(Request $request): View
    {
        $templates = $request->user()->availableTemplates();

        return view('propet.campaigns.create', [
            'templates' => $templates,
            'selected' => (int) old('message_template_id', $templates->first()?->id),
            'maxRecipients' => Setting::int('max_recipients'),
            'requireApproval' => Setting::bool('require_approval'),
            'skipInvalid' => Setting::bool('skip_invalid'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // L'agent ne peut utiliser que les messages qui lui sont autorisés (et actifs).
        $allowedIds = $request->user()->availableTemplates()->pluck('id')->all();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'message_template_id' => ['required', 'integer', Rule::in($allowedIds)],
            'emails' => ['required', 'string', 'max:200000'],
        ], [
            'message_template_id.in' => 'Ce message ne fait pas partie de ceux qui vous sont autorisés.',
        ]);

        $template = MessageTemplate::findOrFail($data['message_template_id']);

        $parsed = EmailListParser::parse($data['emails']);
        $max = Setting::int('max_recipients');

        if ($parsed['valid'] === []) {
            throw ValidationException::withMessages(['emails' => 'Aucune adresse email valide trouvée.']);
        }
        if (count($parsed['valid']) > $max) {
            throw ValidationException::withMessages(['emails' => "La liste dépasse la limite de $max adresses."]);
        }

        $campaign = $this->service->submit($request->user(), $template, $data['title'], $parsed['valid']);

        $stats = $campaign->stats();
        $msg = count($parsed['valid']).' adresse(s) enregistrée(s) avec le message « '.$template->name.' ».';
        if ($stats['invalid'] > 0) {
            $msg .= " {$stats['invalid']} adresse(s) écartée(s) après vérification.";
        }
        if ($parsed['duplicates'] > 0) {
            $msg .= " {$parsed['duplicates']} doublon(s) ignoré(s).";
        }

        return redirect()
            ->route('propet.campaigns.show', $campaign)
            ->with('success', $msg)
            ->with('invalid', $parsed['invalid']);
    }

    public function show(Campaign $campaign): View
    {
        Gate::authorize('view', $campaign);

        return view('propet.campaigns.show', [
            'campaign' => $campaign,
            'stats' => $campaign->stats(),
            'recipients' => $campaign->recipients()->with('response:id,campaign_recipient_id,updated_at')->orderBy('id')->paginate(50),
            // Réponses complètes des clients, affichées directement dans la page de la liste
            'responses' => $campaign->responses()->latest('updated_at')->paginate(10, ['*'], 'reponses'),
        ]);
    }
}
