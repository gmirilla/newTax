<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('module_access')->nullable()->after('role');
        });

        $allOn  = json_encode([
            'invoices'      => true,
            'expenses'      => true,
            'inventory'     => true,
            'manufacturing' => true,
            'payroll'       => true,
            'reports'       => true,
            'bank_accounts' => true,
        ]);

        $allOff = json_encode([
            'invoices'      => false,
            'expenses'      => false,
            'inventory'     => false,
            'manufacturing' => false,
            'payroll'       => false,
            'reports'       => false,
            'bank_accounts' => false,
        ]);

        DB::table('users')
            ->whereIn('role', ['admin', 'accountant'])
            ->update(['module_access' => $allOn]);

        DB::table('users')
            ->where('role', 'staff')
            ->update(['module_access' => $allOff]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('module_access');
        });
    }
};
