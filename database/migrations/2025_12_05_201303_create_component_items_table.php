<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained('grading_components')->onDelete('cascade');
            $table->string('item_name'); // e.g., "Quiz 1", "Activity 1"
            $table->decimal('max_score', 8, 2); // Maximum possible score
            $table->date('date')->nullable();
            $table->timestamps();
            
            $table->index('component_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_items');
    }
};
