<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_informations', function (Blueprint $table) {
            $table->id();

            $table->string('destination_country')->default('China');
            $table->string('visa_type')->default('Tourist Visa');

            $table->string('processing_time')->nullable();
            $table->string('validity')->nullable();
            $table->string('entry_type')->nullable();

            $table->decimal('visa_fee', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');

            $table->json('required_documents')->nullable();

            $table->text('description')->nullable();
            $table->text('terms_conditions')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index([
                'destination_country',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_informations');
    }
};
