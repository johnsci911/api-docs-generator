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
        Schema::create('api_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_endpoint_id')
                ->constrained('api_endpoints')
                ->onDelete('cascade');
            $table->string('name');
            $table->string('type', 50);
            $table->enum('location', ['query', 'path', 'header', 'cookie', 'body']);
            $table->boolean('required')->default(false);
            $table->text('description')->nullable();
            $table->string('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->timestamps();

            $table->index('api_endpoint_id');
            $table->index('is_required');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_parameters');
    }
};
