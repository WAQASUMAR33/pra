<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MerchantConfig', function (Blueprint $table) {
            $table->id();
            $table->string('posId', 50);
            $table->string('token', 255);
            $table->string('branchName', 100);
            $table->string('branchAddress', 255);
            $table->string('apiUrl', 255)->default('https://ims.pral.com.pk/ims/sandbox/api/Live/PostData');
            $table->boolean('isActive')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MerchantConfig');
    }
};
