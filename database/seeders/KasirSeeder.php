<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Jalankan dengan: php artisan db:seed --class=KasirSeeder
 *
 * Menambahkan user kasir default tanpa menghapus data yang sudah ada.
 */
class KasirSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::first();

        if (!$branch) {
            $this->command->error('Tidak ada branch. Jalankan DatabaseSeeder dulu.');
            return;
        }

        // Branch Admin
        if (User::where('email', 'admin@kinayapos.com')->exists()) {
            $this->command->info('User admin@kinayapos.com sudah ada, skip.');
        } else {
            $branchAdmin = User::create([
                'name'     => 'Admin Toko',
                'email'    => 'admin@kinayapos.com',
                'password' => Hash::make('admin1234'),
            ]);
            $branchAdmin->branches()->attach($branch->id, ['role' => 'branch_admin', 'is_default' => true]);
            $this->command->info("✅ Admin dibuat: admin@kinayapos.com / admin1234 (branch: {$branch->name})");
        }

        // Kasir
        if (User::where('email', 'kasir@kinayapos.com')->exists()) {
            $this->command->info('User kasir@kinayapos.com sudah ada, skip.');
        } else {
            $kasir = User::create([
                'name'     => 'Kasir',
                'email'    => 'kasir@kinayapos.com',
                'password' => Hash::make('kasir1234'),
            ]);
            $kasir->branches()->attach($branch->id, ['role' => 'cashier', 'is_default' => true]);
            $this->command->info("✅ Kasir dibuat: kasir@kinayapos.com / kasir1234 (branch: {$branch->name})");
        }
    }
}
