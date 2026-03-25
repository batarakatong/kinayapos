<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Tambah kolom ke branches ──────────────────────────────────────────
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('branches', 'logo')) {
                $table->string('logo')->nullable()->after('email');
            }
            if (!Schema::hasColumn('branches', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('branches', 'bank_account')) {
                $table->string('bank_account')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('branches', 'bank_holder')) {
                $table->string('bank_holder')->nullable()->after('bank_account');
            }
            if (!Schema::hasColumn('branches', 'tax_id')) {
                $table->string('tax_id')->nullable()->after('bank_holder');
            }
            if (!Schema::hasColumn('branches', 'notes')) {
                $table->text('notes')->nullable()->after('tax_id');
            }
        });

        // ── Billing Packages / Pricing Plans ─────────────────────────────────
        Schema::create('billing_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                          // Basic, Pro, Enterprise
            $table->string('slug')->unique();                                // basic, pro, enterprise
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 15, 2)->default(0);            // harga/bulan
            $table->decimal('price_quarterly', 15, 2)->default(0);          // harga/3 bulan
            $table->decimal('price_yearly', 15, 2)->default(0);             // harga/tahun
            $table->json('features')->nullable();                            // ['fitur1','fitur2',...]
            $table->integer('max_users')->default(5);                        // max user per branch
            $table->integer('max_branches')->default(1);                     // max branch
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Tambah kolom ke billings (link ke package) ────────────────────────
        Schema::table('billings', function (Blueprint $table) {
            if (!Schema::hasColumn('billings', 'package_id')) {
                $table->foreignId('package_id')->nullable()
                    ->after('branch_id')
                    ->constrained('billing_packages')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('billings', 'period_start')) {
                $table->string('period_start')->nullable()->after('billing_date');
            }
            if (!Schema::hasColumn('billings', 'period_end')) {
                $table->string('period_end')->nullable()->after('period_start');
            }
            if (!Schema::hasColumn('billings', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('billings', 'payment_proof')) {
                $table->string('payment_proof')->nullable()->after('payment_method');
            }
        });

        // ── Rename notification_branches → sudah ada, pastikan kolom lengkap ─
        // (tabel notification_branches sudah dibuat di migration sebelumnya)
        // Tambah kolom extra jika belum ada
        if (Schema::hasTable('notification_branches')) {
            Schema::table('notification_branches', function (Blueprint $table) {
                if (!Schema::hasColumn('notification_branches', 'delivered_at')) {
                    $table->timestamp('delivered_at')->nullable()->after('read_at');
                }
            });
        }

        // ── Tambah kolom ke notifications ────────────────────────────────────
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'image')) {
                $table->string('image')->nullable()->after('body');
            }
            if (!Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url')->nullable()->after('image');
            }
            if (!Schema::hasColumn('notifications', 'is_draft')) {
                $table->boolean('is_draft')->default(false)->after('sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['image', 'action_url', 'is_draft']);
        });

        if (Schema::hasTable('notification_branches') && Schema::hasColumn('notification_branches', 'delivered_at')) {
            Schema::table('notification_branches', function (Blueprint $table) {
                $table->dropColumn('delivered_at');
            });
        }

        Schema::table('billings', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn(['package_id', 'period_start', 'period_end', 'payment_method', 'payment_proof']);
        });

        Schema::dropIfExists('billing_packages');

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['email', 'logo', 'bank_name', 'bank_account', 'bank_holder', 'tax_id', 'notes']);
        });
    }
};
