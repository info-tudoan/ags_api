<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('zone_id')->constrained('work_zones')->onDelete('restrict');
            $table->enum('status', ['scheduled', 'confirmed', 'cancelled'])->default('scheduled');
            $table->timestamps();

            $table->unique(['shift_id', 'user_id']);
            $table->index('user_id');
            $table->index('zone_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_assignments');
    }
};
