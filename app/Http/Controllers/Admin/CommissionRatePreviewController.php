<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\CommissionGenerator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommissionRatePreviewController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/CommissionRatePreview');
    }

    public function run(Request $request)
    {
        $validated = $request->validate([
            'sale_amount'      => 'required|numeric|min:0',
            'earning_agent_id' => 'required|exists:agents,id',
            'source_agent_id'  => 'nullable|exists:agents,id',
        ]);

        $earner = Agent::findOrFail($validated['earning_agent_id']);
        $generator = app(CommissionGenerator::class);
        $rows = $generator->regenerateConfigPreview($earner, (float) $validated['sale_amount']);

        return response()->json(['rows' => $rows]);
    }
}
