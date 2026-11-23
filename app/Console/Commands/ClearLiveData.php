<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class ClearLiveData extends Command
{
    protected $signature = 'crm:clear-live-data {--force : Required acknowledgement for the irreversible production reset}';

    protected $description = 'Clear live CRM records while preserving users, permissions, and CRM configuration';

    public function handle(): int
    {
        if (! app()->environment('production') || config('database.default') !== 'mysql') {
            $this->error('This command can run only against the production MySQL application.');

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            $this->error('Refusing to clear data without --force.');

            return self::FAILURE;
        }

        if (! $this->confirm('Permanently delete CRM records while preserving users, permissions, and configuration?', false)) {
            $this->warn('No data was changed.');

            return self::FAILURE;
        }

        // These tables contain login/access or application configuration, not CRM records.
        $preserved = [
            'migrations',
            'users',
            'roles',
            'password_reset_tokens',
            'notification_settings',
            'lead_settings',
            'lead_connections',
            'job_roles',
            'task_statuses',
            'sidebar_groups',
            'sidebar_group_items',
            'sidebar_item_orders',
            'lead_backup_settings',
        ];
        $tables = collect(Schema::getTableListing())
            ->map(fn (string $table) => Str::afterLast($table, '.'))
            ->reject(fn (string $table) => in_array($table, $preserved, true))
            ->values();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Live CRM records cleared. Users, roles, permissions, and configuration were preserved.');

        return self::SUCCESS;
    }
}
