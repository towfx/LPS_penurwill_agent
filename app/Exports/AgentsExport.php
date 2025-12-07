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

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Profile Type',
            'Individual Name',
            'Individual Phone',
            'Individual Address',
            'Company Name',
            'Company Representative',
            'Company Registration Number',
            'Company Address',
            'Company Phone',
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
            'Updated At'
        ];
    }

    /**
     * @param mixed $agent
     * @return array
     */
    public function map($agent): array
    {
        return [
            $agent->id,
            $agent->profile_type,
            $agent->individual_name,
            $agent->individual_phone,
            $agent->individual_address,
            $agent->company_name,
            $agent->company_representative_name,
            $agent->company_registration_number,
            $agent->company_address,
            $agent->company_phone,
            $agent->status,
            $agent->user_email,
            $agent->bankAccount?->account_name,
            $agent->bankAccount?->account_number,
            $agent->bankAccount?->bank_name,
            $agent->bankAccount?->iban,
            $agent->bankAccount?->swift_code,
            $agent->referralCode?->code,
            $agent->referralCode?->commission_rate,
            $agent->referralCode?->usage_limit,
            $agent->created_at?->format('Y-m-d H:i:s'),
            $agent->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
