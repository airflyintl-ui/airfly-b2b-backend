<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_requests', function (Blueprint $table) {
            $table->id();

            // Agent information
            $table->unsignedBigInteger('agent_id')->index();

            // Visa information
            $table->unsignedBigInteger('visa_information_id')->nullable()->index();
            $table->string('destination_country')->default('China');
            $table->string('visa_type')->default('Tourist Visa');

            // Passenger information
            $table->string('passenger_name');
            $table->string('passport_number');
            $table->string('email')->nullable();
            $table->string('mobile');
            $table->text('remarks')->nullable();

            // Uploaded documents
            // Stores document URLs and Cloudinary public IDs
            $table->json('documents')->nullable();

            // Visa fee snapshot
            $table->decimal('visa_fee', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');

            // Admin approval
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending')->index();

            $table->text('admin_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_requests');
    }
};
