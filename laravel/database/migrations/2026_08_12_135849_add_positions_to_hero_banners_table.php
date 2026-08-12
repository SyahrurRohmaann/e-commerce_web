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
        Schema::table('hero_banners', function (Blueprint $table) {
            $table->enum('title_position', ['tl','tc','tr','ml','mc','mr','bl','bc','br'])->default('tc')->after('image_url');
            $table->enum('caption_position', ['tl','tc','tr','ml','mc','mr','bl','bc','br'])->nullable()->default(null)->after('title_position');
            $table->enum('button_position', ['tl','tc','tr','ml','mc','mr','bl','bc','br'])->default('bc')->after('caption_position');
            $table->string('caption', 500)->nullable()->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hero_banners', function (Blueprint $table) {
            $table->dropColumn(['title_position', 'caption_position', 'button_position', 'caption']);
        });
    }
};
