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
        Schema::create('stats_monthly', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7)->unique();
            $table->json('data');
            $table->tinyInteger('is_synced')->default(0);
            $table->timestamps();

            $table->index('month', 'stats_monthly_month_index');
            $table->index('is_synced', 'stats_monthly_is_synced_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stats_monthly');
    }
};
