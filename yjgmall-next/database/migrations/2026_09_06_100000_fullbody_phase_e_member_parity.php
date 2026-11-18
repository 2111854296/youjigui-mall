<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'group_code')) {
                $table->string('group_code', 30)->default('personal')->after('status');
            }
            if (! Schema::hasColumn('users', 'pay_password')) {
                $table->string('pay_password')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'deposit')) {
                $table->decimal('deposit', 12, 2)->default(0)->after('balance');
            }
            if (! Schema::hasColumn('users', 'province')) {
                $table->string('province', 50)->nullable()->after('area');
                $table->string('city', 50)->nullable()->after('province');
                $table->string('district', 50)->nullable()->after('city');
            }
            if (! Schema::hasColumn('users', 'sound')) {
                $table->unsignedTinyInteger('sound')->default(1)->after('avatar');
            }
            if (! Schema::hasColumn('users', 'online_status')) {
                $table->string('online_status', 20)->default('online')->after('sound');
            }
            if (! Schema::hasColumn('users', 'mobile_verified')) {
                $table->boolean('mobile_verified')->default(false);
                $table->boolean('bank_verified')->default(false);
                $table->boolean('truename_verified')->default(false);
                $table->string('truename')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_account')->nullable();
            }
            if (! Schema::hasColumn('users', 'oauth_qq')) {
                $table->string('oauth_qq')->nullable();
                $table->string('oauth_weibo')->nullable();
            }
        });

        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('peer_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'peer_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('trade_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module', 30)->default('sell');
            $table->string('keyword');
            $table->string('area')->nullable();
            $table->decimal('price_min', 12, 2)->nullable();
            $table->decimal('price_max', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mail_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->json('topics')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('account_name')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('ask_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('status', 20)->default('new');
            $table->text('reply')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ask_tickets');
        Schema::dropIfExists('withdraw_requests');
        Schema::dropIfExists('mail_subscriptions');
        Schema::dropIfExists('trade_alerts');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_sessions');

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'group_code', 'pay_password', 'deposit', 'province', 'city', 'district', 'sound',
                'online_status', 'mobile_verified', 'bank_verified', 'truename_verified',
                'truename', 'bank_name', 'bank_account', 'oauth_qq', 'oauth_weibo',
            ] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
