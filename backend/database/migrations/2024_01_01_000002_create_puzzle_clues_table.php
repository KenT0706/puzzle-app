<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('puzzle_clues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('puzzle_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('number');
            $table->enum('direction', ['across', 'down']);
            $table->unsignedInteger('start_row');
            $table->unsignedInteger('start_col');
            $table->string('answer');
            $table->text('clue_text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puzzle_clues');
    }
};
