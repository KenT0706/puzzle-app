<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('puzzle_id')->constrained()->cascadeOnDelete();
            $table->enum('mode', ['individual', 'group']);
            $table->string('join_code', 8)->nullable()->unique();
            $table->json('grid_state')->nullable();
            $table->json('correct_cells')->nullable();
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_sessions');
    }
};
