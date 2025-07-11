<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AgentController extends Controller
{
    /**
     * Query agents with filtering and sorting
     */
    public function query(Request $request): JsonResponse
    {
        $query = Agent::query();

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('individual_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('company_representative_name', 'like', "%{$search}%")
                  ->orWhere('individual_phone', 'like', "%{$search}%")
                  ->orWhere('company_phone', 'like', "%{$search}%")
                  ->orWhere('company_registration_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('profile_type')) {
            $query->where('profile_type', $request->get('profile_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort fields
        $allowedSortFields = ['id', 'individual_name', 'company_name', 'profile_type', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'id';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Get paginated results
        $perPage = $request->get('per_page', 15);
        $agents = $query->paginate($perPage);

        // Transform data for frontend
        $transformedAgents = $agents->getCollection()->map(function ($agent) {
            return [
                'id' => $agent->id,
                'agent_type' => ucfirst($agent->profile_type),
                'name' => $agent->profile_type === 'individual'
                    ? $agent->individual_name
                    : $agent->company_name,
                'reg_date' => $agent->created_at->format('Y-m-d'),
                'status' => ucfirst($agent->status),
                'phone' => $agent->profile_type === 'individual'
                    ? $agent->individual_phone
                    : $agent->company_phone,
                'address' => $agent->profile_type === 'individual'
                    ? $agent->individual_address
                    : $agent->company_address,
                'representative' => $agent->company_representative_name,
                'registration_number' => $agent->company_registration_number,
            ];
        });

        return response()->json([
            'data' => $transformedAgents,
            'pagination' => [
                'current_page' => $agents->currentPage(),
                'last_page' => $agents->lastPage(),
                'per_page' => $agents->perPage(),
                'total' => $agents->total(),
                'from' => $agents->firstItem(),
                'to' => $agents->lastItem(),
            ]
        ]);
    }

    /**
     * Get a single agent by ID
     */
    public function show($id): JsonResponse
    {
        $agent = Agent::with('users')->findOrFail($id);

        return response()->json([
            'id' => $agent->id,
            'profile_type' => $agent->profile_type,
            'individual_name' => $agent->individual_name,
            'individual_phone' => $agent->individual_phone,
            'individual_address' => $agent->individual_address,
            'company_representative_name' => $agent->company_representative_name,
            'company_name' => $agent->company_name,
            'company_registration_number' => $agent->company_registration_number,
            'company_address' => $agent->company_address,
            'company_phone' => $agent->company_phone,
            'status' => $agent->status,
            'created_at' => $agent->created_at->format('Y-m-d H:i:s'),
            'user_email' => $agent->users->first()?->email,
        ]);
    }
}
