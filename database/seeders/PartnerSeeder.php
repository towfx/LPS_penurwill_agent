<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PartnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $partners = [
            [
                'id' => 1,
                'parent_id' => 0,
                'company_name' => 'Vdurya Management Services Sdn Bhd',
                'company_registration_number' => '200701028225 / 0786247D',
                'company_address' => 'C-2-6, Jalan Todak 4, Sunway Business Park, Seberang Jaya, 13700 Prai, Penang, Malaysia',
                'company_phone' => '04-398 5095, 016-4161823, 012-448 3514',
                'company_email' => 'info@vdurya.com', // Assuming a generic info email, update as needed
                'status' => 'active',
                'code' => 'VDURYA2024',
                'user_email' => 'info@vdurya.com',
                'user_password' => 'passw123',
            ],
            [
                'parent_id' => 1,
                'company_name' => 'Beta Enterprise Solutions',
                'company_registration_number' => 'BETA-2024-002',
                'company_address' => '456 Enterprise Avenue, Selangor, Malaysia',
                'company_phone' => '+60198765432',
                'company_email' => 'beta@partners.com',
                'status' => 'active',
                'code' => 'BETA2024',
                'user_email' => 'beta@partners.com',
                'user_password' => 'passw123',
            ],
            [
                'parent_id' => 1,
                'company_name' => 'Gamma Trading Group',
                'company_registration_number' => 'GAMMA-2024-003',
                'company_address' => '789 Trading Boulevard, Penang, Malaysia',
                'company_phone' => '+60187654321',
                'company_email' => 'gamma@partners.com',
                'status' => 'active',
                'code' => 'GAMMA2024',
                'user_email' => 'gamma@partners.com',
                'user_password' => 'passw123',
            ],
        ];

        foreach ($partners as $partnerData) {
            // Create partner
            $partner = Partner::create([
                'company_name' => $partnerData['company_name'],
                'company_registration_number' => $partnerData['company_registration_number'],
                'company_address' => $partnerData['company_address'],
                'company_phone' => $partnerData['company_phone'],
                'company_email' => $partnerData['company_email'],
                'code' => $partnerData['code'],
                'status' => $partnerData['status'],
                'parent_id' => $partnerData['parent_id'],
            ]);

            // Create user
            $user = User::create([
                'name' => $partnerData['company_name'],
                'email' => $partnerData['user_email'],
                'password' => Hash::make($partnerData['user_password']),
                'email_verified_at' => now(),
            ]);

            // Assign partner role
            $user->assignRole('partner');

            // Link user to partner
            $user->partners()->attach($partner->id);
        }
    }
}
