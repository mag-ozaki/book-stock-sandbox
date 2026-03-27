<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamp('sold_at');
            $table->string('pos_terminal_id', 100)->nullable();
            $table->timestamps();
            $table->index(['store_id', 'sold_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_histories');
    }
};
