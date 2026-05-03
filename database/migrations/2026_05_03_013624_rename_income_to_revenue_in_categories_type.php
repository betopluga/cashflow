<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update existing data first
        DB::table('categories')->where('type', 'income')->update(['type' => 'revenue']);

        // Modify the ENUM column
        DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('revenue', 'expense') NOT NULL");
    }

    public function down(): void
    {
        DB::table('categories')->where('type', 'revenue')->update(['type' => 'income']);
        DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('income', 'expense') NOT NULL");
    }
};
