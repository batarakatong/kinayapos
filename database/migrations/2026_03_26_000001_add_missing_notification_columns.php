<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Failsafe migration — menambah kolom notifications yang mungkin belum ada
 * akibat migration sebelumnya gagal atau sudah dicatat sebagai "ran".
 */
return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(array_filter(
                ['image', 'action_url', 'is_draft'],
                fn($col) => Schema::hasColumn('notifications', $col)
            ));
        });
    }
};
