<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Add uuid to receivables
        Schema::table('receivables', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Add uuid to payables
        Schema::table('payables', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Add note to purchase_items for lot/batch notes
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->text('note')->nullable()->after('total');
        });

        // Back-fill uuids for existing rows
        \DB::table('receivables')->whereNull('uuid')->get()->each(
            fn ($r) => \DB::table('receivables')->where('id', $r->id)->update(['uuid' => (string) Str::uuid()])
        );

        \DB::table('payables')->whereNull('uuid')->get()->each(
            fn ($p) => \DB::table('payables')->where('id', $p->id)->update(['uuid' => (string) Str::uuid()])
        );

        // Make uuid non-nullable after back-fill
        Schema::table('receivables', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('note');
        });

        Schema::table('receivables', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
