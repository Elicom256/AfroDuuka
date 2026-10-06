<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->whereNotNull('business_id')
            ->whereNotNull('role_id')
            ->with('role')
            ->get();

        if ($users->isEmpty()) {
            $this->command?->warn('No business users found; skipping activity log seeding.');

            return;
        }

        $templates = $this->templates();

        $created = 0;

        foreach ($users as $index => $user) {
            foreach ($templates as $offset => $template) {
                // Spread entries over the last few days so date filters have data to work with.
                $occurredAt = now()->subDays($offset % 7)->subMinutes($index * 7 + $offset * 31);
                $description = str_replace(':name', $user->name, $template['description']);

                $exists = ActivityLog::query()
                    ->where('causer_type', $user->getMorphClass())
                    ->where('causer_id', $user->getKey())
                    ->where('log_name', $template['log_name'])
                    ->where('description', $description)
                    ->exists();

                if ($exists) {
                    continue;
                }

                ActivityLog::create([
                    'log_name' => $template['log_name'],
                    'event' => $template['event'],
                    'description' => $description,
                    'causer_type' => $user->getMorphClass(),
                    'causer_id' => $user->getKey(),
                    'business_id' => $user->business_id,
                    'business_branch_id' => $user->business_branch_id,
                    'properties' => $this->properties($template),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'created_at' => $occurredAt,
                    'updated_at' => $occurredAt,
                ]);

                $created++;
            }
        }

        $this->command?->info("Seeded {$created} activity log entries.");
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function templates(): Collection
    {
        return collect([
            [
                'log_name' => 'auth',
                'event' => 'logged_in',
                'description' => ':name logged in',
                'properties' => ['attributes' => ['ip' => '127.0.0.1']],
            ],
            [
                'log_name' => 'permission',
                'event' => 'role_assigned',
                'description' => 'Role assigned to :name',
                'properties' => [
                    'attributes' => ['role' => 'editor'],
                    'old' => ['role' => 'customer'],
                ],
            ],
            [
                'log_name' => 'settings',
                'event' => 'updated',
                'description' => 'Customer settings updated by :name',
                'properties' => [
                    'attributes' => ['credit_limit' => 500000, 'currency' => 'UGX'],
                    'old' => ['credit_limit' => 250000, 'currency' => 'UGX'],
                ],
            ],
            [
                'log_name' => 'data_export',
                'event' => 'exported',
                'description' => 'Report export requested by :name',
                'properties' => [
                    'attributes' => ['report_type' => 'sales', 'format' => 'xlsx'],
                ],
            ],
            [
                'log_name' => 'customer',
                'event' => 'updated',
                'description' => 'Customer record updated by :name',
                'properties' => [
                    'attributes' => ['name' => 'Acme Traders', 'phone' => '+256700000001'],
                    'old' => ['name' => 'Acme Trading', 'phone' => '+256700000000'],
                ],
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $template
     * @return array<string, mixed>
     */
    private function properties(array $template): array
    {
        return array_merge(['ip' => '127.0.0.1'], $template['properties']);
    }
}
