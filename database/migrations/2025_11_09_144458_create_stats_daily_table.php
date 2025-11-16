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
        Schema::create('stats_daily', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->json('data');
            $table->tinyInteger('is_synced')->default(0);
            $table->timestamps();

            $table->index('date', 'stats_daily_date_index');
            $table->index('is_synced', 'stats_daily_is_synced_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_daily');
    }
};
