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
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('camera_config')->nullable()->after('description');
            $table->string('category')->nullable()->after('camera_config');
            $table->text('output_category')->nullable()->after('category');
            $table->string('image_metadata')->nullable()->after('output_category');
            $table->date('capture_date')->nullable()->after('image_metadata');
            $table->string('terrain_path')->nullable()->after('capture_date');
            $table->string('building_path')->nullable()->after('terrain_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropColumn(['camera_config', 'category', 'output_category', 'image_metadata', 'capture_date', 'terrain_path', 'building_path']);
        });
    }
};
