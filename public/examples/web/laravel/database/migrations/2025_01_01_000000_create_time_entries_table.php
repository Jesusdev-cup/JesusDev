<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The domain owns its schema. request_id is unique — the database
 * itself enforces idempotency, not just app code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('task');
            $table->unsignedInteger('minutes');
            $table->string('request_id')->unique(); // dedup key
            $table->timestamps();

            $table->index('created_at'); // listForDay queries by day
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
