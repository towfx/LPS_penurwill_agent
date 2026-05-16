<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\CommissionGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaleSimulationController extends Controller
{
    public function index()
    {
        $agents = Agent::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'agent_role']);

        return Inertia::render('Admin/SaleSimulation', [
            'agents' => $agents,
        ]);
    }

    public function run(Request $request)
    {
        $validated = $request->validate([
            'agent_id'    => 'required|exists:agents,id',
            'sale_amount' => 'required|numeric|min:0.01',
        ]);

        $agent = Agent::findOrFail($validated['agent_id']);
        $rows  = app(CommissionGenerator::class)
            ->regenerateConfigPreview($agent, (float) $validated['sale_amount']);

        return response()->json(['rows' => $rows]);
    }
}
