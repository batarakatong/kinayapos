<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $branch = Branch::create([
            'name' => 'Pusat',
            'code' => 'PST',
            'address' => 'Jl. Utama No.1',
            'phone' => '0800000000',
        ]);

        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@kinaya.test',
            'password' => Hash::make('password'),
        ]);

        $admin->branches()->attach($branch->id, ['role' => 'super_admin', 'is_default' => true]);

        // Branch Admin — akses penuh di cabang (sama seperti admin di Flutter)
        $branchAdmin = User::create([
            'name'     => 'Admin Toko',
            'email'    => 'admin@kinayapos.com',
            'password' => Hash::make('admin1234'),
        ]);

        $branchAdmin->branches()->attach($branch->id, ['role' => 'branch_admin', 'is_default' => true]);

        // Default kasir account
        $kasir = User::create([
            'name' => 'Kasir',
            'email' => 'kasir@kinayapos.com',
            'password' => Hash::make('kasir1234'),
        ]);

        $kasir->branches()->attach($branch->id, ['role' => 'cashier', 'is_default' => true]);
    }
}
