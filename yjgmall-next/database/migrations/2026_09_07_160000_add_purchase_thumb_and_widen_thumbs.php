<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('purchases', 'thumb')) {
                $table->string('thumb', 500)->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'thumb')) {
                $table->dropColumn('thumb');
            }
        });
    }
};
