<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('id_client_bp');
            $table->string('rfc')->nullable();
            $table->string('tax_regime')->nullable();
            $table->string('full_name');
            $table->string('gender')->nullable();
            $table->string('contact_method')->nullable();
            $table->string('phone_1')->nullable();
            $table->string('phone_2')->nullable();
            $table->string('phone_3')->nullable();
            $table->string('cellphone')->nullable();
            $table->string('email_1')->nullable();
            $table->string('email_2')->nullable();
            $table->string('city')->nullable();
            $table->string('delegacy')->nullable();
            $table->string('colony')->nullable();
            $table->string('address')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('type')->nullable();
            $table->string('picture')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict')->onUpdate('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};