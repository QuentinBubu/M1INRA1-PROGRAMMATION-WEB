<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user', function (Blueprint $table) {
            $table->integer('user_id', true);

            $table->text('user_login');
            $table->longText('user_password');

            $table->integer('user_compte_id')->unique('cleetrangere');

            $table->text('user_mail');

            $table->timestamp('user_date_new')->useCurrent();
            $table->timestamp('user_date_login')->useCurrent()->useCurrentOnUpdate();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};
