<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('shift_assignment_id')->constrained('shift_assignments')->onDelete('restrict');
            $table->timestamp('check_in_time');
            $table->timestamp('check_out_time')->nullable();
            $table->json('check_in_location');
            $table->json('check_out_location')->nullable();
            $table->string('check_in_wifi_bssid')->nullable();
            $table->string('check_out_wifi_bssid')->nullable();
            $table->boolean('check_in_mfa_verified')->default(false);
            $table->boolean('check_out_mfa_verified')->default(false);
            $table->integer('total_time_in_zone')->nullable();
            $table->enum('status', ['on_time', 'early', 'delayed', 'absent'])->default('on_time');
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'check_in_time']);
            $table->index('shift_assignment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
