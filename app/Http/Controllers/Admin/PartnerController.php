<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PartnerController extends Controller
{
    /**
     * Display a listing of partners
     */
    public function index(Request $request)
    {
        $query = Partner::with(['parent', 'users', 'agents']);

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('company_email', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $partners = $query->paginate(15)->withQueryString();

        $partners->getCollection()->transform(function ($partner) {
            return [
                'id' => $partner->id,
                'company_name' => $partner->company_name,
                'code' => $partner->code,
                'status' => ucfirst($partner->status),
                'parent_name' => $partner->parent ? $partner->parent->company_name : null,
                'agents_count' => $partner->agents->count(),
                'users_count' => $partner->users->count(),
                'created_at' => $partner->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return Inertia::render('Admin/PartnersList', [
            'partners' => $partners->items(),
            'pagination' => [
                'current_page' => $partners->currentPage(),
                'last_page' => $partners->lastPage(),
                'per_page' => $partners->perPage(),
                'total' => $partners->total(),
                'from' => $partners->firstItem(),
                'to' => $partners->lastItem(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new partner
     */
    public function create()
    {
        $partners = Partner::select('id', 'company_name')->get();

        return Inertia::render('Admin/PartnersAdd', [
            'partners' => $partners,
        ]);
    }

    /**
     * Store a newly created partner
     */
    public function store(Request $request)
    {
        $adminUser = Auth::user();

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_registration_number' => 'required|string|max:255',
            'company_address' => 'required|string',
            'company_phone' => 'required|string|max:255',
            'company_email' => 'required|email|unique:partners,company_email',
            'status' => 'required|in:active,inactive,suspended',
            'code' => 'required|string|max:255|unique:partners,code',
            'parent_id' => 'nullable|exists:partners,id',
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required|string|min:8|confirmed',
            'company_profile_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Create partner
            $partnerData = [
                'company_name' => $request->company_name,
                'company_registration_number' => $request->company_registration_number,
                'company_address' => $request->company_address,
                'company_phone' => $request->company_phone,
                'company_email' => $request->company_email,
                'status' => $request->status,
                'code' => $request->code,
                'parent_id' => $request->parent_id ?? 0,
            ];

            $partner = Partner::create($partnerData);

            // Handle file upload
            if ($request->hasFile('company_profile_file')) {
                $file = $request->file('company_profile_file');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40).'.'.$extension;
                $path = "partner/{$partner->id}/{$filename}";

                Storage::disk('local')->put($path, file_get_contents($file));
                $partner->update(['company_profile_file' => $path]);
            }

            // Log partner creation
            ActivityLog::logCreate($adminUser, $partner, $partner->toArray());

            // Create user
            $user = User::create([
                'name' => $request->company_name,
                'email' => $request->user_email,
                'password' => Hash::make($request->user_password),
                'email_verified_at' => now(),
            ]);

            // Log user creation
            ActivityLog::logCreate($adminUser, $user, $user->toArray());

            // Assign partner role
            $user->assignRole('partner');

            // Log role assignment
            ActivityLog::logCustom($adminUser, 'role_assigned', "Admin assigned 'partner' role to user {$user->email}", $user);

            // Link user to partner
            $user->partners()->attach($partner->id);

            // Log user-partner relationship creation
            ActivityLog::logCustom($adminUser, 'user_partner_linked', "Admin linked user {$user->email} to partner {$partner->id}", $partner);

            DB::commit();

            return redirect()->route('admin.partners.list')->with('success', 'Partner created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to create partner. '.$e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified partner
     */
    public function show($id)
    {
        $partner = Partner::with(['parent', 'children', 'users', 'agents'])->findOrFail($id);

        return Inertia::render('Admin/PartnerView', [
            'partner' => [
                'id' => $partner->id,
                'parent_id' => $partner->parent_id,
                'parent_name' => $partner->parent ? $partner->parent->company_name : null,
                'company_name' => $partner->company_name,
                'company_registration_number' => $partner->company_registration_number,
                'company_address' => $partner->company_address,
                'company_phone' => $partner->company_phone,
                'company_email' => $partner->company_email,
                'status' => $partner->status,
                'code' => $partner->code,
                'company_profile_file' => $partner->company_profile_file,
                'created_at' => $partner->created_at->format('Y-m-d H:i:s'),
                'users' => $partner->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                }),
                'agents_count' => $partner->agents->count(),
                'children_count' => $partner->children->count(),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified partner
     */
    public function edit($id)
    {
        $partner = Partner::with(['parent', 'users'])->findOrFail($id);
        $partners = Partner::where('id', '!=', $id)->select('id', 'company_name')->get();

        return Inertia::render('Admin/PartnerUpdate', [
            'id' => $id,
            'partner' => [
                'id' => $partner->id,
                'parent_id' => $partner->parent_id,
                'company_name' => $partner->company_name,
                'company_registration_number' => $partner->company_registration_number,
                'company_address' => $partner->company_address,
                'company_phone' => $partner->company_phone,
                'company_email' => $partner->company_email,
                'status' => $partner->status,
                'code' => $partner->code,
                'company_profile_file' => $partner->company_profile_file,
                'user_email' => $partner->users->first()?->email,
            ],
            'partners' => $partners,
        ]);
    }

    /**
     * Update the specified partner
     */
    public function update(Request $request, $id)
    {
        $adminUser = Auth::user();
        $partner = Partner::findOrFail($id);

        // Capture before data for activity logging
        $beforeData = $partner->toArray();

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_registration_number' => 'required|string|max:255',
            'company_address' => 'required|string',
            'company_phone' => 'required|string|max:255',
            'company_email' => 'required|email|unique:partners,company_email,'.$id,
            'status' => 'required|in:active,inactive,suspended',
            'code' => 'required|string|max:255|unique:partners,code,'.$id,
            'parent_id' => 'nullable|exists:partners,id',
            'user_password' => 'nullable|string|min:8|confirmed',
            'company_profile_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Update partner
            $partnerData = [
                'company_name' => $request->company_name,
                'company_registration_number' => $request->company_registration_number,
                'company_address' => $request->company_address,
                'company_phone' => $request->company_phone,
                'company_email' => $request->company_email,
                'status' => $request->status,
                'code' => $request->code,
                'parent_id' => $request->parent_id ?? 0,
            ];

            // Handle file upload
            if ($request->hasFile('company_profile_file')) {
                // Delete old file if exists
                if ($partner->company_profile_file && Storage::disk('local')->exists($partner->company_profile_file)) {
                    Storage::disk('local')->delete($partner->company_profile_file);
                }

                $file = $request->file('company_profile_file');
                $extension = $file->getClientOriginalExtension();
                $filename = Str::random(40).'.'.$extension;
                $path = "partner/{$partner->id}/{$filename}";

                Storage::disk('local')->put($path, file_get_contents($file));
                $partnerData['company_profile_file'] = $path;
            }

            $partner->update($partnerData);

            // Update user password if provided
            $user = $partner->users->first();
            if ($user && $request->filled('user_password')) {
                $user->update([
                    'password' => Hash::make($request->user_password),
                ]);

                // Log password update
                ActivityLog::logCustom($adminUser, 'password_updated', "Admin updated password for user {$user->email}", $user);
            }

            // Refresh partner data
            $partner->refresh();

            // Capture after data for activity logging
            $afterData = $partner->toArray();

            // Log the partner update activity
            ActivityLog::logUpdate($adminUser, $partner, $beforeData, $afterData);

            DB::commit();

            return redirect()->route('admin.partners.list')->with('success', 'Partner updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to update partner. '.$e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified partner
     */
    public function destroy($id)
    {
        $adminUser = Auth::user();
        $partner = Partner::with(['users', 'agents'])->findOrFail($id);

        // Check if partner has agents
        if ($partner->agents->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete partner with associated agents.']);
        }

        DB::beginTransaction();
        try {
            // Delete file if exists
            if ($partner->company_profile_file && Storage::disk('local')->exists($partner->company_profile_file)) {
                Storage::disk('local')->deleteDirectory("partner/{$partner->id}");
            }

            // Detach users
            $partner->users()->detach();

            // Log deletion
            ActivityLog::logCustom($adminUser, 'partner_deleted', "Admin deleted partner {$partner->company_name}", $partner);

            // Delete partner
            $partner->delete();

            DB::commit();

            return redirect()->route('admin.partners.list')->with('success', 'Partner deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Failed to delete partner. '.$e->getMessage()]);
        }
    }
}
