<?php

namespace App\Exports;

use App\Models\Agent;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AgentsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Agent::with(['users', 'bankAccount', 'referralCode'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Profile Type',
            'Individual Name',
            'Individual Phone',
            'Individual Email',
            'Individual Address',
            'Company Name',
            'Company Representative',
            'Company Registration Number',
            'Company Address',
            'Company Phone',
            'Company Email Address',
            'Status',
            'User Email',
            'Bank Account Name',
            'Bank Account Number',
            'Bank Name',
            'IBAN',
            'Swift Code',
            'Referral Code',
            'Commission Rate',
            'Usage Limit',
            'Created At',
            'Updated At',
        ];
    }

    /**
     * @param  mixed  $agent
     */
    public function map($agent): array
    {
        return [
            $agent->id,
            $agent->profile_type,
            $agent->individual_name,
            $agent->individual_phone,
            $agent->individual_email,
            $agent->individual_address,
            $agent->company_name,
            $agent->company_representative_name,
            $agent->company_registration_number,
            $agent->company_address,
            $agent->company_phone,
            $agent->company_email_address,
            $agent->status,
            $agent->user_email,
            $agent->bankAccount?->account_name,
            $agent->bankAccount?->account_number,
            $agent->bankAccount?->bank_name,
            $agent->bankAccount?->iban,
            $agent->bankAccount?->swift_code,
            $agent->referralCode?->code,
            $agent->referralCode?->commission_rate,
            $agent->created_at?->format('Y-m-d H:i:s'),
            $agent->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
