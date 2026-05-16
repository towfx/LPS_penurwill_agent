<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HierarchyController extends Controller
{
    /**
     * Show downline hierarchy for the authenticated agent.
     */
    public function index()
    {
        $user = auth()->user();
        $agent = $user->agents()->first();

        if (! $agent) {
            abort(403, 'Not an agent');
        }

        $nodes = collect([$agent])->merge($agent->descendants());

        $rootId = (string) $agent->id;
        $settings = SystemSetting::first();
        $labels = [
            'agent' => $settings->role_name_agent ?? 'Agent',
            'agent_leader' => $settings->role_name_leader ?? 'Leader',
            'business_partner' => $settings->role_name_business_partner ?? 'Business Partner',
        ];

        $data = $nodes->map(function ($a) use ($rootId, $labels) {
            $parentId = $a->id == (int) $rootId
                ? ''
                : ($a->parent_agent_id ? (string) $a->parent_agent_id : '');
            $role = $a->agent_role ?? 'agent';

            return [
                'id' => (string) $a->id,
                'parentId' => $parentId,
                'name' => $a->name,
                'title' => strtoupper($labels[$role] ?? str_replace('_', ' ', $role)),
                'imageUrl' => $a->profile_image ? Storage::url($a->profile_image) : null,
            ];
        })->values();

        return Inertia::render('Agent/AgentHierarchy', [
            'hierarchyData' => $data,
        ]);
    }
}
