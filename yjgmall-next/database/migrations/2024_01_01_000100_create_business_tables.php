<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('module', 30)->default('sell');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image');
            $table->string('link')->nullable();
            $table->string('position', 30)->default('home');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('logo')->nullable();
            $table->string('contact')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->text('intro')->nullable();
            $table->text('content')->nullable();
            $table->string('status', 20)->default('pending');
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('thumb')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('brand')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'refreshed_at']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->decimal('budget', 12, 2)->nullable();
            $table->string('quantity')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('thumb')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->string('unit', 30)->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('sales')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('thumb')->nullable();
            $table->string('author')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('type', 30)->default('news');
            $table->boolean('is_top')->default(false);
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 30)->default('pending');
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_trade_no')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('receiver_address')->nullable();
            $table->text('remark')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('favoritable_type');
            $table->unsignedBigInteger('favoritable_id');
            $table->timestamps();
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'favorites_unique');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('products');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('supplies');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('categories');
    }
};
