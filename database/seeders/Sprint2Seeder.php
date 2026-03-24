<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class Sprint2Seeder extends Seeder
{
    public function run(): void
    {
        // Suppliers
        $suppliers = [
            ['name' => 'PT Sumber Jaya', 'phone' => '08111000001', 'email' => 'sj@example.com', 'address' => 'Jakarta'],
            ['name' => 'CV Maju Bersama', 'phone' => '08111000002', 'email' => 'mb@example.com', 'address' => 'Bandung'],
            ['name' => 'UD Berkah Niaga',  'phone' => '08111000003', 'email' => 'bn@example.com', 'address' => 'Surabaya'],
        ];

        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(['email' => $s['email']], $s);
        }

        // Customers
        $customers = [
            ['name' => 'Walk-in Customer', 'phone' => null,            'email' => null,                    'branch_id' => null],
            ['name' => 'Toko Mawar',        'phone' => '08122000001', 'email' => 'mawar@example.com',     'branch_id' => null],
            ['name' => 'Toko Melati',       'phone' => '08122000002', 'email' => 'melati@example.com',    'branch_id' => null],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['name' => $c['name']],
                $c
            );
        }

        // Sample products (global)
        $products = [
            ['name' => 'Beras 5kg',        'sku' => 'BRS-5KG',   'barcode' => '8991234500001', 'price' => 75000, 'cost' => 60000, 'tax_rate' => 0, 'track_stock' => true],
            ['name' => 'Minyak Goreng 1L', 'sku' => 'MG-1L',     'barcode' => '8991234500002', 'price' => 20000, 'cost' => 16000, 'tax_rate' => 0, 'track_stock' => true],
            ['name' => 'Gula Pasir 1kg',   'sku' => 'GP-1KG',    'barcode' => '8991234500003', 'price' => 15000, 'cost' => 12000, 'tax_rate' => 0, 'track_stock' => true],
            ['name' => 'Sabun Cuci',        'sku' => 'SC-BTL',    'barcode' => '8991234500004', 'price' => 12000, 'cost' => 9000,  'tax_rate' => 0, 'track_stock' => true],
        ];

        foreach ($products as $p) {
            \App\Models\Product::firstOrCreate(
                ['sku' => $p['sku']],
                ['uuid' => (string) Str::uuid(), ...$p, 'is_global' => true, 'is_active' => true]
            );
        }
    }
}
