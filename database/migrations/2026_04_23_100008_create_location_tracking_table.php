<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('location_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('accuracy_meters');
            $table->foreignId('in_zone_id')->nullable()->constrained('work_zones')->onDelete('set null');
            $table->boolean('is_in_zone');
            $table->enum('gps_source', ['gps', 'wifi', 'network'])->default('gps');
            $table->json('device_info')->nullable();
            $table->dateTime('timestamp');
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'timestamp']);
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_tracking');
    }
};
