<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_lines', function (Blueprint $table) {
            $table->text('text_from_files')->nullable()->after('text');
            $table->text('dirty_locales')->nullable()->after('text_from_files');
            $table->boolean('orphan')->default(false)->after('dirty_locales');
        });
    }

    public function down(): void
    {
        Schema::table('language_lines', function (Blueprint $table) {
            $table->dropColumn([
                'text_from_files',
                'dirty_locales',
                'orphan',
            ]);
        });
    }
};
