<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('shipping_country')->nullable()->after('shipping_address');
            $table->string('shipping_province')->nullable()->after('shipping_country');
            $table->string('shipping_sub_district')->nullable()->after('shipping_city');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('country')->nullable()->after('address');
            $table->string('province')->nullable()->after('country');
            $table->string('sub_district')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['shipping_country', 'shipping_province', 'shipping_sub_district']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['country', 'province', 'sub_district']);
        });
    }
};
