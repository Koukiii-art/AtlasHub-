<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->date('date');
            $table->decimal('price', 10, 2);
            $table->integer('numberOfPlaces');
            $table->string('category');
            $table->string('location');
            $table->string('image')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index('date');
            $table->index('category');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};