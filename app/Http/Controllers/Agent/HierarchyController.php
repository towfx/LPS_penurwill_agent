<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
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

        $data = $nodes->map(function ($a) use ($rootId) {
            $parentId = $a->id == (int) $rootId
                ? ''
                : ($a->parent_agent_id ? (string) $a->parent_agent_id : '');

            return [
                'id' => (string) $a->id,
                'parentId' => $parentId,
                'name' => $a->name,
                'title' => strtoupper(str_replace('_', ' ', $a->agent_role ?? 'agent')),
                'imageUrl' => $a->profile_image ? Storage::url($a->profile_image) : null,
            ];
        })->values();

        return Inertia::render('Agent/AgentHierarchy', [
            'hierarchyData' => $data,
        ]);
    }
}
