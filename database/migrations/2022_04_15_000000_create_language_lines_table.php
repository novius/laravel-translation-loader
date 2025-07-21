<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('language_lines')) {
            Schema::create('language_lines', static function (Blueprint $table) {
                $table->increments('id');
                $table->string('namespace')->default('*');
                $table->string('group')->index();
                $table->string('key');
                $table->text('text');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::drop('language_lines');
    }
};
