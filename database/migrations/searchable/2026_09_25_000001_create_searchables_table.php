<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lives in database/migrations/searchable/ on purpose: SynapseServiceProvider
 * only auto-loads database/migrations/ (non-recursive), so this table is only
 * created by `php artisan synapps:searchable-setup`.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('searchables')) {
            return;
        }

        Schema::create('searchables', function (Blueprint $table) {
            $table->id();
            $table->string('source', 100);
            $table->string('key', 191);
            $table->string('locale', 10)->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon', 100)->nullable();
            $table->string('route')->nullable();
            $table->json('route_params')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('acl')->nullable();
            $table->text('keywords')->nullable();
            $table->timestamps();

            $table->index(['source', 'key']);
            $table->index('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('searchables');
    }
};
