<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->timestamps();
        });

        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('prize_id')->nullable()->after('won_at')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prize_id');
        });

        Schema::dropIfExists('prizes');
    }
};
