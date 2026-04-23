<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['primary', 'secondary', 'break']);
            $table->text('description')->nullable();
            $table->json('coordinates');
            $table->integer('radius')->nullable();
            $table->integer('min_gps_accuracy')->default(20);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('type');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_zones');
    }
};
