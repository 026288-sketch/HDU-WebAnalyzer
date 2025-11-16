<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('content');
            $table->text('summary')->nullable(); // після content
            $table->string('url', 767)->unique(); // або text() при потребі
            $table->timestamp('timestamp')->nullable(); // дата публікації
            $table->string('hash')->unique(); // md5(title + content)
            $table->text('image')->nullable(); // шлях до зображення
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('nodes');
    }
};
