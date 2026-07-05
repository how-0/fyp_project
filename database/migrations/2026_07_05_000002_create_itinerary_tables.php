<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itineraries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('location');
            $table->string('country', 2)->default('MY');
            $table->string('currency', 3)->default('MYR');
            $table->unsignedTinyInteger('duration_days');
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->json('activity_preferences')->nullable();
            $table->string('travel_style')->default('mid-range');
            $table->string('pace')->default('moderate');
            $table->date('start_date')->nullable();
            $table->enum('status', ['draft', 'generated', 'finalized'])->default('generated');
            $table->decimal('total_estimated_cost', 10, 2)->default(0);
            $table->json('budget_breakdown')->nullable();
            $table->text('summary')->nullable();
            $table->json('tips')->nullable();
            $table->string('ai_prompt_hash')->nullable();
            $table->timestamps();
        });

        Schema::create('itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->date('date')->nullable();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['itinerary_id', 'day_number']);
        });

        Schema::create('itinerary_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('itinerary_day_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('place_id')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('category')->default('other');
            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->enum('cost_source', ['ai', 'places', 'manual'])->default('ai');
            $table->unsignedTinyInteger('price_level')->nullable();
            $table->boolean('is_ai_suggested')->default(true);
            $table->boolean('user_modified')->default(false);
            $table->string('search_query')->nullable();
            $table->timestamps();
        });

        Schema::create('places_cache', function (Blueprint $table) {
            $table->id();
            $table->string('place_id')->unique();
            $table->string('name');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->unsignedTinyInteger('price_level')->nullable();
            $table->json('types')->nullable();
            $table->timestamp('cached_at');
            $table->timestamps();
        });

        Schema::create('destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('state');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destinations');
        Schema::dropIfExists('places_cache');
        Schema::dropIfExists('itinerary_activities');
        Schema::dropIfExists('itinerary_days');
        Schema::dropIfExists('itineraries');
    }
};
