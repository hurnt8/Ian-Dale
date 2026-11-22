<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /** Un propet ne voit que ses propres listes ; l'admin voit tout. */
    public function view(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin() || $campaign->user_id === $user->id;
    }
}
