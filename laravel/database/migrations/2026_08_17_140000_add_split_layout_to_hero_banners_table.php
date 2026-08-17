<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_banners', function (Blueprint $table) {
            $table->string('layout_direction', 20)->default('text-left')->after('image_url');
            $table->string('panel_theme', 20)->default('ivory')->after('layout_direction');
            $table->string('image_position', 20)->default('50% 50%')->after('panel_theme');
            $table->string('text_alignment', 20)->default('left')->after('image_position');
        });
    }

    public function down(): void
    {
        Schema::table('hero_banners', function (Blueprint $table) {
            $table->dropColumn([
                'layout_direction',
                'panel_theme',
                'image_position',
                'text_alignment',
            ]);
        });
    }
};
