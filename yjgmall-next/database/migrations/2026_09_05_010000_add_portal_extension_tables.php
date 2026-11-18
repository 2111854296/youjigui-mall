<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('position', 40)->index(); // home_banner, sell_right, mall_top, footer...
            $table->string('type', 20)->default('image'); // image|text|code
            $table->string('image')->nullable();
            $table->string('link')->nullable();
            $table->text('content')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('friend_links', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('logo')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('link')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('contact_name');
            $table->string('contact_phone', 30);
            $table->string('contact_email')->nullable();
            $table->string('title');
            $table->text('content');
            $table->string('status', 20)->default('new');
            $table->timestamps();
            $table->index(['target_type', 'target_id']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_certified')->default(false)->after('is_featured');
            $table->string('website')->nullable()->after('phone');
            $table->string('area')->nullable()->after('address');
            $table->string('business')->nullable()->after('area');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('vip_expire_at')->nullable()->after('level');
            $table->boolean('is_certified')->default(false)->after('vip_expire_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vip_expire_at', 'is_certified']);
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['is_certified', 'website', 'area', 'business']);
        });
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('friend_links');
        Schema::dropIfExists('ads');
    }
};
