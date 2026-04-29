<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // MySQL ENUM: add 'shift_manager' value
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('employee','team_lead','shift_manager','admin','hr') NOT NULL DEFAULT 'employee'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('employee','team_lead','admin','hr') NOT NULL DEFAULT 'employee'");
    }
};
