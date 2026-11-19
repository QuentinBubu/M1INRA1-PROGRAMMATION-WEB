<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table): void {
            $table->integer('user_id')->nullable()->after('id');
            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
