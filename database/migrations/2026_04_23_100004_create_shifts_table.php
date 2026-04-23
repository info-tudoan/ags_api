<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_template_id')->constrained('shift_templates')->onDelete('restrict');
            $table->date('shift_date');
            $table->foreignId('team_lead_id')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['shift_date', 'team_lead_id']);
            $table->index('team_lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
