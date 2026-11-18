<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('company_name')->nullable();
            $table->string('area')->nullable();
            $table->string('salary')->nullable();
            $table->string('education')->nullable();
            $table->string('experience')->nullable();
            $table->unsignedInteger('headcount')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('status', 20)->default('approved');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('know_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->unsignedInteger('reward')->default(0);
            $table->string('status', 20)->default('open');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('answers_count')->default(0);
            $table->timestamps();
        });

        Schema::create('know_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('know_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('content');
            $table->boolean('is_best')->default(false);
            $table->string('status', 20)->default('published');
            $table->timestamps();
        });

        Schema::create('club_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('circle', 50)->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('approved');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('replies')->default(0);
            $table->timestamps();
        });

        Schema::create('specials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('thumb')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('thumb')->nullable();
            $table->json('images')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('approved');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('thumb')->nullable();
            $table->string('video_url')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('approved');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('group_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('thumb')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('market_price', 12, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('sold')->default(0);
            $table->timestamp('ends_at')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('approved');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('exhibits', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('area')->nullable();
            $table->string('venue')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('organizer')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('published');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('thumb')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 20)->default('published');
            $table->timestamps();
        });

        Schema::create('gift_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gift_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('points');
            $table->string('status', 20)->default('pending');
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone', 30)->nullable();
            $table->string('receiver_address')->nullable();
            $table->timestamps();
        });

        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('accepted');
            $table->timestamps();
            $table->unique(['user_id', 'friend_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_posts');
        Schema::dropIfExists('know_answers');
        Schema::dropIfExists('know_questions');
        Schema::dropIfExists('club_posts');
        Schema::dropIfExists('specials');
        Schema::dropIfExists('photos');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('group_deals');
        Schema::dropIfExists('exhibits');
        Schema::dropIfExists('gift_orders');
        Schema::dropIfExists('gifts');
        Schema::dropIfExists('friendships');
    }
};
