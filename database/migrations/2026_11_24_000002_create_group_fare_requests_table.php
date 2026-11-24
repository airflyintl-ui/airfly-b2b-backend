<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_fare_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_fare_offer_id')
                ->constrained('group_fare_offers')
                ->cascadeOnDelete();

            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();

            $table->unsignedInteger('passenger_count');

            $table->string('contact_person');
            $table->string('contact_number');
            $table->string('contact_email')->nullable();

            $table->string('document_path')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected'
            ])->default('pending');

            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->index(['agent_id', 'status']);
            $table->index(['group_fare_offer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_fare_requests');
    }
};
