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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id('id_complaint');
            $table->foreignId('id_users')->constrained('users', 'id_users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('id_location')->constrained('locations', 'id_location')->onDelete('cascade')->onUpdate('cascade');
            $table->string('complaint');
            $table->dateTime('complaint_date')->useCurrent();
            // $table->string('Photo_Video')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
