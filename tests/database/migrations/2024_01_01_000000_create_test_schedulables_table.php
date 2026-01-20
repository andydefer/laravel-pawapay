<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating the test schedulables table.
 *
 * This table is used for testing schedulable entities in the Roster package.
 * It provides a simple structure to test entity scheduling functionality.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the test_schedulables table with necessary columns for testing.
     */
    public function up(): void
    {
        if (!Schema::hasTable('test_schedulables')) {
            Schema::create('test_schedulables', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
                $table->string('name')->default('Dr. John Doe');
                $table->string('specialty')->default('cardiology');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Drops the test_schedulables table.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_schedulables');
    }
};
