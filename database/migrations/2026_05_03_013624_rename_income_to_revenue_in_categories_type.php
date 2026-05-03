<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand ENUM to accept both old and new values
        DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('income', 'expense', 'revenue') NOT NULL");

        // 2. Migrate data
        DB::table('categories')->where('type', 'income')->update(['type' => 'revenue']);

        // 3. Remove the old value
        DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('revenue', 'expense') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('revenue', 'expense', 'income') NOT NULL");
        DB::table('categories')->where('type', 'revenue')->update(['type' => 'income']);
        DB::statement("ALTER TABLE categories MODIFY COLUMN type ENUM('income', 'expense') NOT NULL");
    }
};
