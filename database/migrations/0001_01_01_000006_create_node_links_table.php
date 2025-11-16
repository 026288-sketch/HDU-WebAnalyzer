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
        Schema::create('node_links', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->string('source')->nullable();
            $table->enum('type', ['rss', 'html'])->default('html');
            $table->boolean('use_browser')->default(false);
            $table->boolean('parsed')->default(false);
            $table->integer('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('node_links');
    }
};
