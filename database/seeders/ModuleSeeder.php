<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'key' => 'clients',
                'name' => 'CRM',
                'description' => 'Store client information, companies, status, tags, and CRM notes.',
                'enabled_by_default' => true,
            ],
            [
                'key' => 'tickets',
                'name' => 'Support Tickets',
                'description' => 'Track and manage support tickets assigned to your team.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'chat',
                'name' => 'Live Chat',
                'description' => 'Realtime live chat between your team and website visitors.',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'email',
                'name' => 'Email Marketing',
                'description' => 'Lists, subscribers, templates, and campaigns with open/click tracking.',
                'enabled_by_default' => false,
            ],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(['key' => $module['key']], $module);
        }
    }
}
