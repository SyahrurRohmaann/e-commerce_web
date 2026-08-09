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
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change(); // Allow guest
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->enum('shipping_status', ['pending', 'shipping', 'arrive'])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->dropColumn([
                'customer_name',
                'customer_phone',
                'shipping_address',
                'shipping_city',
                'shipping_postal_code',
                'shipping_cost',
                'shipping_status'
            ]);
        });
    }
};
