<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            if (! Schema::hasColumn('inquiries', 'reply')) {
                $table->text('reply')->nullable()->after('content');
            }
            if (! Schema::hasColumn('inquiries', 'replied_at')) {
                $table->timestamp('replied_at')->nullable()->after('reply');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $cols = array_filter(['reply', 'replied_at'], fn ($c) => Schema::hasColumn('inquiries', $c));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
