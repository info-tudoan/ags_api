<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('zone_wifi_networks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('work_zones')->onDelete('cascade');
            $table->string('bssid');
            $table->string('ssid')->nullable();
            $table->string('signal_strength')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['zone_id', 'bssid']);
            $table->index('zone_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zone_wifi_networks');
    }
};
