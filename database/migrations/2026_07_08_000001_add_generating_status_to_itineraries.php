<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE itineraries MODIFY status ENUM('draft', 'generating', 'generated', 'finalized') NOT NULL DEFAULT 'generated'");
    }

    public function down(): void
    {
        DB::statement("UPDATE itineraries SET status = 'draft' WHERE status = 'generating'");
        DB::statement("ALTER TABLE itineraries MODIFY status ENUM('draft', 'generated', 'finalized') NOT NULL DEFAULT 'generated'");
    }
};
