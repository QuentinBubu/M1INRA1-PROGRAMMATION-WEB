<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user', function ($table): void {
            $table->string('role')->default('client')->after('user_mail');
        });

        DB::table('user')
            ->where('user_compte_id', 1)
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('user', function ($table): void {
            $table->dropColumn('role');
        });
    }
};
