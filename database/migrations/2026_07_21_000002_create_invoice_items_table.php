<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('InvoiceItem', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoiceId');
            $table->string('itemCode', 50);
            $table->string('itemName', 150);
            $table->integer('quantity');
            $table->string('pctCode', 20)->nullable();
            $table->decimal('taxRate', 5, 2);
            $table->decimal('saleValue', 12, 2);
            $table->decimal('salesTaxApplicable', 12, 2);
            $table->decimal('furtherTax', 12, 2)->default(0.00);
            $table->decimal('federalTax', 12, 2)->default(0.00);
            $table->decimal('discount', 12, 2)->default(0.00);
            $table->decimal('netAmount', 12, 2);
            $table->timestamps();

            $table->foreign('invoiceId')->references('id')->on('Invoice')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('InvoiceItem');
    }
};
