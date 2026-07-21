<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Invoice', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoiceNumber', 50);
            $table->string('posId', 50);
            $table->string('usin', 100)->unique();
            $table->dateTime('dateTime')->useCurrent();
            $table->string('buyerNtn', 20)->nullable();
            $table->string('buyerCnic', 20)->nullable();
            $table->string('buyerName', 100)->nullable();
            $table->string('buyerPhone', 20)->nullable();
            $table->integer('invoiceType')->default(1);
            $table->integer('totalQuantity');
            $table->decimal('totalSaleValue', 12, 2);
            $table->decimal('totalTaxCharged', 12, 2);
            $table->decimal('totalDiscount', 12, 2)->default(0.00);
            $table->decimal('totalBillAmount', 12, 2);
            $table->integer('paymentMode')->default(1);
            $table->string('status', 20)->default('DRAFT');
            $table->string('praFiscalNumber', 100)->nullable();
            $table->string('praResponseCode', 10)->nullable();
            $table->text('praResponseMsg')->nullable();
            $table->string('eventType', 50)->nullable();
            $table->dateTime('eventDate')->nullable();
            $table->integer('numberOfGuests')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Invoice');
    }
};
