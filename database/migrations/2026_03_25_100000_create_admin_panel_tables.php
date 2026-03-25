<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Billing per branch ──────────────────────────────────────────────
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('plan')->default('basic');       // basic | pro | enterprise
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('billing_date');                   // tanggal tagihan
            $table->date('due_date');                       // jatuh tempo
            $table->date('paid_at')->nullable();
            $table->enum('status', ['unpaid', 'paid', 'overdue', 'cancelled'])->default('unpaid');
            $table->string('invoice_number')->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'status']);
        });

        // ── Notifications / Announcements ───────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->enum('type', ['announcement', 'update', 'billing', 'alert'])->default('announcement');
            $table->boolean('is_broadcast')->default(true);  // true = semua branch
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable();   // null = kirim sekarang
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        // ── Notification targets (branch-specific) ─────────────────────────
        Schema::create('notification_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->unique(['notification_id', 'branch_id']);
        });

        // ── SMTP Settings (global or per-branch) ────────────────────────────
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // null = global/default SMTP
            $table->string('driver')->default('smtp');       // smtp | sendmail | mailgun
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(587);
            $table->enum('encryption', ['tls', 'ssl', 'none'])->default('tls');
            $table->string('username');
            $table->text('password');                        // encrypted
            $table->string('from_address');
            $table->string('from_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ── Daily Report Schedule per branch ────────────────────────────────
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->time('send_at')->default('08:00:00');    // jam kirim
            $table->string('recipients');                    // email CSV
            $table->json('report_types')->nullable();        // ['sales','stocks','purchases']
            $table->timestamps();
            $table->unique('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
        Schema::dropIfExists('smtp_settings');
        Schema::dropIfExists('notification_branches');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('billings');
    }
};
