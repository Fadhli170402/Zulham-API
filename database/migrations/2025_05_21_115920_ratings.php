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
        Schema::create('rating', function (Blueprint $table) {
            $table->id('id_rating');
            $table->foreignId('id_users')->constrained('users', 'id_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('id_tour')->constrained('tours', 'id_tour')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('value');
            $table->text('comment')->nullable();
            $table->date('rating_date')->default(now());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rating');
    }
};
