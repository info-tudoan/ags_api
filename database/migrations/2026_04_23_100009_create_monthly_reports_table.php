<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('month');
            $table->integer('total_work_days');
            $table->decimal('total_hours_worked', 8, 2);
            $table->integer('total_delays_minutes');
            $table->integer('total_deducted_minutes');
            $table->decimal('effective_hours', 8, 2);
            $table->dateTime('generated_at');
            $table->foreignId('generated_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->unique(['user_id', 'month']);
            $table->index('user_id');
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_reports');
    }
};
