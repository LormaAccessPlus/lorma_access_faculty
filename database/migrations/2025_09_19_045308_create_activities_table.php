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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['lecture', 'lab']);
            $table->enum('term', ['prelim', 'midterm', 'finals']);
            $table->decimal('max_score', 8, 2);
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('gcr_assignment_id')->nullable();
            $table->timestamps();

            $table->index(['subject_id', 'type', 'term']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
