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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('id_order_bp');
            $table->dateTime('service_date')->nullable();
            $table->dateTime('service_billing_date')->nullable();
            $table->dateTime('sale_billing_date')->nullable();
            $table->double('gross_price', 15, 3);
            $table->double('tax_price', 15, 3);
            $table->double('total_price', 15, 3);
            $table->double('order_km');
            $table->text('observations')->nullable();
            $table->string('order_type')->nullable();
            $table->string('order_category')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('customer_fact_id')->nullable();
            $table->unsignedBigInteger('customer_contact_id')->nullable();
            $table->unsignedBigInteger('customer_legal_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('sales_executive_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('sales_executive_id')->references('id')->on('sales_executives')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};