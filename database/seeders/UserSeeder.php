<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\SpecSeederSupport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use SpecSeederSupport;

    public function run(): void
    {
        $config = $this->specConfig();

        $this->upsertAdmin();

        for ($i = 1; $i <= $config['total_agents']; $i++) {
            $this->upsertUser(
                sprintf('agent%02d@test.com', $i),
                sprintf('Agent %02d', $i),
                'agent',
                '0811' . str_pad((string) $i, 8, '0', STR_PAD_LEFT)
            );
        }

        for ($i = 1; $i <= $config['total_tenants']; $i++) {
            $this->upsertUser(
                sprintf('tenant%04d@test.com', $i),
                sprintf('Tenant %04d', $i),
                'tenant',
                '0822' . str_pad((string) $i, 8, '0', STR_PAD_LEFT)
            );
        }
    }

    private function upsertUser(string $email, string $name, string $role, string $phone): void
    {
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => $role,
                'enabled' => 1,
                'email_verified_at' => now(),
                'phone' => $phone,
            ]
        );
    }

    private function upsertAdmin(): void
    {
        $legacyAdmin = User::query()->where('email', 'admin@local.test')->first();

        if ($legacyAdmin) {
            $legacyAdmin->update([
                'email' => 'admin@test.com',
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'enabled' => 1,
                'email_verified_at' => now(),
                'phone' => '080000000001',
            ]);

            return;
        }

        $this->upsertUser('admin@test.com', 'System Admin', 'admin', '080000000001');
    }
}
