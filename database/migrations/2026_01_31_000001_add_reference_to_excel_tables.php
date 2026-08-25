<?php

declare(strict_types=1);

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
        if (Schema::hasTable('excel_exports') && ! Schema::hasColumn('excel_exports', 'reference')) {
            Schema::table('excel_exports', function (Blueprint $table) {
                $table->string('reference')->nullable()->after('user_id')->index();
            });
        }

        if (Schema::hasTable('excel_imports') && ! Schema::hasColumn('excel_imports', 'reference')) {
            Schema::table('excel_imports', function (Blueprint $table) {
                $table->string('reference')->nullable()->after('user_id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('excel_exports') && Schema::hasColumn('excel_exports', 'reference')) {
            Schema::table('excel_exports', function (Blueprint $table) {
                $table->dropIndex(['reference']);
                $table->dropColumn('reference');
            });
        }

        if (Schema::hasTable('excel_imports') && Schema::hasColumn('excel_imports', 'reference')) {
            Schema::table('excel_imports', function (Blueprint $table) {
                $table->dropIndex(['reference']);
                $table->dropColumn('reference');
            });
        }
    }
};
