<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(private CampaignService $service) {}

    public function index(Request $request): View
    {
        $campaigns = Campaign::with('user')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.campaigns.index', [
            'campaigns' => $campaigns,
            'statuses' => CampaignStatus::cases(),
        ]);
    }

    public function show(Campaign $campaign): View
    {
        return view('admin.campaigns.show', [
            'campaign' => $campaign->load('user', 'reviewer'),
            'stats' => $campaign->stats(),
            'recipients' => $campaign->recipients()->with('response:id,campaign_recipient_id,updated_at')->orderBy('id')->paginate(50),
        ]);
    }

    public function approve(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === CampaignStatus::Pending, 422, 'Campagne déjà traitée.');
        $this->service->approve($campaign, $request->user());

        return back()->with('success', 'Campagne validée : envoi automatique lancé.');
    }

    public function reject(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status === CampaignStatus::Pending, 422, 'Campagne déjà traitée.');
        $this->service->reject($campaign, $request->user());

        return back()->with('success', 'Campagne refusée.');
    }

    public function retry(Campaign $campaign): RedirectResponse
    {
        $count = $this->service->retryFailed($campaign);

        return back()->with('success', "$count envoi(s) relancé(s).");
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()->route('admin.campaigns.index')->with('success', 'Campagne supprimée.');
    }
}
