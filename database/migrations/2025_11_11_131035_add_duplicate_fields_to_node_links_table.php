<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('node_links', function (Blueprint $table) {
            // Флаг что это дубликат
            $table->boolean('is_duplicate')->default(false)->after('parsed');

            // Ссылка на оригинальную ноду (если это дубликат)
            $table->foreignId('duplicate_of')
                ->nullable()
                ->after('is_duplicate')
                ->constrained('nodes')
                ->onDelete('set null');

            // Индекс для быстрого поиска дубликатов
            $table->index('is_duplicate');
        });
    }

    public function down(): void
    {
        Schema::table('node_links', function (Blueprint $table) {
            $table->dropForeign(['duplicate_of']);
            $table->dropIndex(['is_duplicate']);
            $table->dropColumn(['is_duplicate', 'duplicate_of']);
        });
    }
};
