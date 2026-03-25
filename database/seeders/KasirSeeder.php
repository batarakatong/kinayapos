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
        // Gunakan branch pertama yang ada
        $branch = Branch::first();

        if (!$branch) {
            $this->command->error('Tidak ada branch. Jalankan DatabaseSeeder dulu.');
            return;
        }

        // Buat user kasir jika belum ada
        if (User::where('email', 'kasir@kinayapos.com')->exists()) {
            $this->command->info('User kasir@kinayapos.com sudah ada, skip.');
            return;
        }

        $kasir = User::create([
            'name'     => 'Kasir',
            'email'    => 'kasir@kinayapos.com',
            'password' => Hash::make('kasir1234'),
        ]);

        $kasir->branches()->attach($branch->id, [
            'role'       => 'cashier',
            'is_default' => true,
        ]);

        $this->command->info("✅ User kasir dibuat: kasir@kinayapos.com / kasir1234 (branch: {$branch->name})");
    }
}
