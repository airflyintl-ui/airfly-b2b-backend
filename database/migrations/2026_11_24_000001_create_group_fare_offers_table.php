<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_fare_offers', function (Blueprint $table) {
            $table->id();

            // Airline Information
            $table->string('airline_name');

            // Journey Sector
            $table->string('origin', 10);
            $table->string('destination', 10);

            // Travel Dates
            $table->date('departure_date');
            $table->date('return_date')->nullable();

            // Fare Information
            $table->decimal('fare', 12, 2);
            $table->string('currency', 10)->default('BDT');

            // Seat Availability
            $table->unsignedInteger('total_seats')->default(0);
            $table->unsignedInteger('available_seats')->default(0);

            // Admin Information
            $table->text('description')->nullable();
            $table->text('terms_conditions')->nullable();

            // draft, published, unpublished
            $table->string('status', 20)->default('draft');

            $table->timestamps();

            $table->index(['status', 'departure_date']);
            $table->index(['origin', 'destination']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_fare_offers');
    }
};
