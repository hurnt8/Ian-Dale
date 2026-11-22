<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Enums\RecipientStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Unsubscribe;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $byStatus = CampaignRecipient::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        // Récapitulatif des emails par propet
        $perPropet = User::query()
            ->where('users.role', Role::Propet->value)
            ->leftJoin('campaigns', 'campaigns.user_id', '=', 'users.id')
            ->leftJoin('campaign_recipients', 'campaign_recipients.campaign_id', '=', 'campaigns.id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total')
            ->get([
                'users.id', 'users.name', 'users.email',
                DB::raw('count(distinct campaigns.id) as lists'),
                DB::raw('count(campaign_recipients.id) as total'),
                DB::raw("sum(case when campaign_recipients.status = 'sent' then 1 else 0 end) as sent"),
                DB::raw("sum(case when campaign_recipients.status = 'failed' then 1 else 0 end) as failed"),
            ]);

        return view('admin.dashboard', [
            'cards' => [
                ['label' => 'Emails au total', 'value' => (int) $byStatus->sum(), 'icon' => 'bi-envelope', 'tone' => 'indigo'],
                ['label' => 'Envoyés', 'value' => (int) ($byStatus[RecipientStatus::Sent->value] ?? 0), 'icon' => 'bi-send-check', 'tone' => 'green'],
                ['label' => 'En attente', 'value' => (int) ($byStatus[RecipientStatus::Pending->value] ?? 0), 'icon' => 'bi-hourglass-split', 'tone' => 'amber'],
                ['label' => 'Échecs', 'value' => (int) ($byStatus[RecipientStatus::Failed->value] ?? 0), 'icon' => 'bi-exclamation-triangle', 'tone' => 'red'],
                ['label' => 'Désinscrits', 'value' => Unsubscribe::count(), 'icon' => 'bi-person-dash', 'tone' => 'gray'],
            ],
            'pending' => Campaign::with('user')->where('status', CampaignStatus::Pending->value)->latest()->take(8)->get(),
            'perPropet' => $perPropet,
        ]);
    }
}
