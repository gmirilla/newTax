<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('acting_on_behalf_of_user_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete()
                ->comment('Real firm employee driving a shared partner-proxy session, if any');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acting_on_behalf_of_user_id');
        });
    }
};
