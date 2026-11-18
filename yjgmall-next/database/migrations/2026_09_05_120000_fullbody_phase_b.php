<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // charge|cash|credit|order|deposit|spread
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2)->nullable();
            $table->string('title');
            $table->text('remark')->nullable();
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'type']);
        });

        Schema::create('purchase_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('quantity')->nullable();
            $table->text('content')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();
        });

        Schema::create('spreads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 30); // sell|buy|company|mall
            $table->string('keyword');
            $table->decimal('bid', 12, 2)->default(0);
            $table->unsignedInteger('days')->default(30);
            $table->string('status', 20)->default('pending');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('place_code', 40);
            $table->string('title');
            $table->string('contact_name');
            $table->string('contact_phone', 30);
            $table->text('remark')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_bookings');
        Schema::dropIfExists('spreads');
        Schema::dropIfExists('purchase_quotes');
        Schema::dropIfExists('finance_records');
    }
};
