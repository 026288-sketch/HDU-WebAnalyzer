<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_sentiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('node_id')->constrained('nodes')->onDelete('cascade')->unique();
            $table->string('sentiment')->nullable(); // негатив, позитив, нейтрал
            $table->string('emotion')->nullable();   // Гнев, Грусть, Отвращение, Страх, Радость, Удивление, Нейтральная
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_sentiments');
    }
};
