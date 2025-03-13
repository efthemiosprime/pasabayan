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
        Schema::create('delivery_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('trip_id')->nullable()->constrained()->onDelete('set null');
            $table->string('pickup_location');
            $table->string('dropoff_location');
            $table->enum('package_size', ['small', 'medium', 'large', 'extra_large']);
            $table->float('package_weight');
            $table->text('package_description');
            $table->enum('urgency', ['low', 'medium', 'high'])->default('medium');
            $table->dateTime('delivery_date');
            $table->enum('status', ['pending', 'accepted', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->text('special_instructions')->nullable();
            $table->float('estimated_cost')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_requests');
    }
};
