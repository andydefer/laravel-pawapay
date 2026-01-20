<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating test cars table.
 *
 * Used in testing for polymorphic schedule attachments.
 */
return new class extends Migration
{
    /**
     * Run the migrations to create test cars table.
     *
     * Creates a table representing vehicles for testing purposes.
     */
    public function up(): void
    {
        if (!Schema::hasTable('test_cars')) {
            Schema::create('test_cars', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
                $table->string('model');
                $table->string('license_plate')->unique();
                $table->string('type')->default('sedan');
                $table->integer('capacity')->default(5);
            });
        }
    }

    /**
     * Reverse the migrations by dropping test cars table.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_cars');
    }
};
