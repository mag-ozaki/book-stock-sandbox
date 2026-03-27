<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('jan_code', 26)->unique()->nullable()->comment('JANコード（上段13桁+下段13桁の26桁）');
            $table->string('title');
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->unsignedInteger('price')->nullable()->comment('円単位');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
