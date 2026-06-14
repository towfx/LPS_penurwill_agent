<?php

namespace Database\Seeders;

use App\Models\TemplateEmail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TemplateEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = config('mail_templates', []);

        foreach ($templates as $ref => $spec) {
            $messages = [];
            foreach ($spec['messages'] as $varName => $msgSpec) {
                $messages[$varName] = $msgSpec['default'];
            }

            TemplateEmail::updateOrCreate(
                ['ref' => $ref],
                [
                    'title'    => $spec['title'],
                    'messages' => $messages,
                ]
            );
        }
    }
}
