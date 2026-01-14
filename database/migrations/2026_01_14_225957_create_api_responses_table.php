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
        Schema::create('api_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')
                ->constrained('api_endpoints')
                ->onDelete('cascade');
            $table->string('status_code');
            $table->text('description')->nullable();
            $table->json('schema')->nullable();
            $table->json('example')->nullable();
            $table->boolean('is_error')->default(false);
            $table->timestamps();

            $table->index('api_endpoint_id');
            $table->index('status_code');
            $table->index('is_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_responses');
    }
};
