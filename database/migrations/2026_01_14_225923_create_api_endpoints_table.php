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
        Schema::create('api_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('uri');
            $table->string('methods');
            $table->string('controller');
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('group', 100)->nullable();
            $table->boolean('is_deprecated')->default(false);
            $table->json('middleware')->nullable();
            $table->timestamps();

            $table->index('uri');
            $table->index('group');
            $table->index('is_deprecated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_endpoints');
    }
};
