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
        Schema::create('student_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('school_student_id')->nullable();
            $table->string('gcr_student_id')->nullable();
            $table->string('student_name');
            $table->string('student_email')->nullable();
            $table->decimal('mapping_confidence', 5, 2)->nullable();
            $table->timestamps();

            $table->index(['subject_id', 'school_student_id']);
            $table->index(['subject_id', 'gcr_student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_mappings');
    }
};
