<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = config('japan-postal-codes.table_name', 'japan_postal_codes');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('postal_code', 7)->index();          // e.g. 1600023
            $table->string('postal_code_formatted', 8)->index(); // e.g. 160-0023

            $table->string('prefecture');
            $table->string('city');
            $table->string('town')->nullable();

            $table->string('prefecture_kana')->nullable();
            $table->string('city_kana')->nullable();
            $table->string('town_kana')->nullable();

            $table->string('prefecture_romaji')->nullable();
            $table->string('city_romaji')->nullable();
            $table->string('town_romaji')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('japan-postal-codes.table_name', 'japan_postal_codes');

        Schema::dropIfExists($tableName);
    }
};
