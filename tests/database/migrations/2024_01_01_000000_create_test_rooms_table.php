<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for creating test rooms table.
 *
 * Used in testing for polymorphic schedule attachments.
 */
return new class extends Migration
{
    /**
     * Run the migrations to create test rooms table.
     *
     * Creates a table representing physical rooms for testing purposes.
     */
    public function up(): void
    {
        if (!Schema::hasTable('test_rooms')) {
            Schema::create('test_rooms', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
                $table->string('name');
                $table->integer('capacity')->default(10);
                $table->string('type')->default('meeting');
            });
        }
    }

    /**
     * Reverse the migrations by dropping test rooms table.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_rooms');
    }
};
