<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puzzles', function (Blueprint $table) {
            $table->string('share_code', 16)->nullable()->unique()->after('id');
        });

        // Give every existing puzzle its own unguessable code.
        foreach (DB::table('puzzles')->whereNull('share_code')->pluck('id') as $id) {
            DB::table('puzzles')->where('id', $id)->update(['share_code' => Str::lower(Str::random(10))]);
        }
    }

    public function down(): void
    {
        Schema::table('puzzles', function (Blueprint $table) {
            $table->dropColumn('share_code');
        });
    }
};