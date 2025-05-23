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
        Schema::create('medias', function (Blueprint $table) {
            $table->id('id_media');
            // $table->foreignId('id_complaint')->constrained('complaints')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('id_complaint')->constrained('complaints', 'id_complaint')->onDelete('cascade')->onUpdate('cascade');
            $table->string('path');
            $table->enum('media_type', ['image', 'video']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
