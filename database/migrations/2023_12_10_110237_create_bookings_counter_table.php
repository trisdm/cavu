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
        Schema::create('booking_counters', function (Blueprint $table) {
            $table->id();
            $table->string('external_booking_id');
            $table->dateTime('booking_day');
            $table->enum('status', ['booked','canceled','amended']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings_counter');
    }
};
