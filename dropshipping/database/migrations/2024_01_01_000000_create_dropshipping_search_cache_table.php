<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dropshipping_search_cache', function (Blueprint $table) {
            $table->id();
            $table->string('search_query')->index(); // The product search query
            $table->string('search_hash')->unique(); // MD5 hash of normalized query for fast lookup
            $table->json('search_results'); // JSON data containing the search results
            $table->integer('total_websites')->default(0); // Number of websites found
            $table->json('search_summary')->nullable(); // Summary statistics
            $table->boolean('is_active')->default(true); // Admin can enable/disable cached results
            $table->timestamp('last_used_at')->nullable(); // Track when this cache was last accessed
            $table->integer('usage_count')->default(0); // How many times this cache has been used
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['search_hash', 'is_active']);
            $table->index(['last_used_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dropshipping_search_cache');
    }
};