<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attraction_prices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('state');
            $table->string('place_id')->nullable()->unique();
            $table->json('aliases')->nullable();
            $table->string('category')->nullable();
            $table->decimal('price_myr', 10, 2);
            $table->string('price_label')->nullable();
            $table->string('source_name');
            $table->string('source_url', 500)->nullable();
            $table->date('price_as_of')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'state']);
            $table->index('is_active');
        });

        Schema::table('itinerary_activities', function (Blueprint $table) {
            $table->foreignId('attraction_price_id')->nullable()->after('cost_source')->constrained('attraction_prices')->nullOnDelete();
            $table->string('price_source_name')->nullable()->after('attraction_price_id');
            $table->string('price_source_url', 500)->nullable()->after('price_source_name');
        });

        DB::statement("ALTER TABLE itinerary_activities MODIFY COLUMN cost_source ENUM('ai', 'places', 'manual', 'catalog') NOT NULL DEFAULT 'ai'");
    }

    public function down(): void
    {
        Schema::table('itinerary_activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attraction_price_id');
            $table->dropColumn(['price_source_name', 'price_source_url']);
        });

        DB::statement("ALTER TABLE itinerary_activities MODIFY COLUMN cost_source ENUM('ai', 'places', 'manual') NOT NULL DEFAULT 'ai'");

        Schema::dropIfExists('attraction_prices');
    }
};
