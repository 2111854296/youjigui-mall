<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'template')) {
                $table->string('template', 30)->default('default')->after('is_certified');
            }
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->string('country')->nullable();
            $table->text('intro')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('file_url');
            $table->string('file_type', 30)->nullable();
            $table->string('file_size', 30)->nullable();
            $table->text('summary')->nullable();
            $table->unsignedInteger('downloads')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 20)->default('published');
            $table->timestamps();
        });

        Schema::create('invest_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('area')->nullable();
            $table->string('amount')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('invest_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invest_project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('contact_name');
            $table->string('contact_phone', 30);
            $table->text('content')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('content');
            $table->string('status', 20)->default('published');
            $table->timestamps();
            $table->unique(['product_id', 'user_id']);
        });

        Schema::create('article_topics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('article_topic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_topic_id')->constrained()->cascadeOnDelete();
            $table->unique(['article_id', 'article_topic_id']);
        });

        Schema::create('hotwords', function (Blueprint $table) {
            $table->id();
            $table->string('word');
            $table->string('module', 30)->default('sell');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 20)->default('published');
            $table->timestamps();
            $table->unique(['company_id', 'slug']);
        });

        Schema::create('company_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_links');
        Schema::dropIfExists('company_pages');
        Schema::dropIfExists('hotwords');
        Schema::dropIfExists('article_topic');
        Schema::dropIfExists('article_topics');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('invest_leads');
        Schema::dropIfExists('invest_projects');
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('brands');
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'template')) {
                $table->dropColumn('template');
            }
        });
    }
};
