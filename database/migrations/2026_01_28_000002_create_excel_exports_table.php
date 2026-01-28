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
        Schema::create('excel_exports', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('pending');
            $table->string('output_path')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('query_class')->nullable();
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('processed_rows')->default(0);
            $table->json('options')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('excel_exports');
    }
};
