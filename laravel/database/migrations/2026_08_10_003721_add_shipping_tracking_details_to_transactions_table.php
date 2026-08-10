<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('shipping_method')->nullable()->after('shipping_status');
            $table->string('shipping_courier')->nullable()->after('shipping_method');
            $table->string('tracking_number')->nullable()->after('shipping_courier');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['shipping_method', 'shipping_courier', 'tracking_number']);
        });
    }
};
