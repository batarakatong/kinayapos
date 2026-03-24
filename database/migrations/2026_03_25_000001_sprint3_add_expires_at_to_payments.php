<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 3 — add QRIS expiry timestamp to payments.
 *
 * Midtrans QRIS charges expire after 15 minutes by default.
 * The mobile client can use `expires_at` to show a countdown
 * and stop polling the status endpoint after expiry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Nullable: only set for QRIS payments.
            $table->timestamp('expires_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
