<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating test doctors table.
 *
 * Used in testing for polymorphic schedule attachments.
 */
return new class extends Migration
{
    /**
     * Run the migrations to create test doctors table.
     *
     * Creates a table representing medical professionals for testing purposes.
     */
    public function up(): void
    {
        if (!Schema::hasTable('test_doctors')) {
            Schema::create('test_doctors', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
                $table->string('name');
                $table->string('specialty')->default('general');
                $table->string('email')->unique()->nullable();
            });
        }
    }

    /**
     * Reverse the migrations by dropping test doctors table.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_doctors');
    }
};
