<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplies', function (Blueprint $table) {
            $table->string('area')->nullable()->after('brand');
            $table->decimal('min_amount', 12, 2)->nullable()->after('unit');
            $table->string('amount')->nullable()->after('min_amount');
            $table->unsignedInteger('days')->nullable()->after('amount');
            $table->string('thumb1')->nullable()->after('thumb');
            $table->string('thumb2')->nullable()->after('thumb1');
            $table->string('attr1_name')->nullable();
            $table->string('attr1_value')->nullable();
            $table->string('attr2_name')->nullable();
            $table->string('attr2_value')->nullable();
            $table->string('attr3_name')->nullable();
            $table->string('attr3_value')->nullable();
            $table->boolean('is_urgent')->default(false)->after('status');
            $table->boolean('is_elite')->default(false)->after('is_urgent');
            $table->unsignedTinyInteger('level')->default(0)->after('is_elite');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('area')->nullable()->after('quantity');
            $table->string('unit', 30)->nullable()->after('quantity');
            $table->unsignedInteger('days')->nullable();
            $table->boolean('is_urgent')->default(false)->after('status');
            $table->unsignedTinyInteger('level')->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('member_type', 20)->default('company')->after('username'); // personal|company
            $table->string('gender', 10)->nullable()->after('name');
            $table->string('qq', 30)->nullable()->after('mobile');
            $table->string('area')->nullable()->after('company_name');
            $table->string('company_type', 30)->nullable()->after('area');
            $table->unsignedInteger('credit')->default(0)->after('points');
        });

        Schema::create('vip_grades', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->decimal('price_year', 12, 2)->default(0);
            $table->unsignedTinyInteger('level')->default(0);
            $table->text('perks')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vip_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vip_grade_id')->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('contact_name');
            $table->string('phone', 30)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('qq', 30)->nullable();
            $table->text('remark')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        Schema::create('ad_places', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('type', 20)->default('image');
            $table->string('size', 40)->nullable();
            $table->string('price_note')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('status', 20)->default('published');
            $table->timestamps();
        });

        Schema::create('company_honors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('image')->nullable();
            $table->string('authority')->nullable();
            $table->date('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit', 30)->nullable();
            $table->decimal('price_min', 12, 2)->nullable();
            $table->decimal('price_max', 12, 2)->nullable();
            $table->decimal('price_avg', 12, 2)->nullable();
            $table->string('market')->nullable();
            $table->date('quoted_on')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
        Schema::dropIfExists('quote_products');
        Schema::dropIfExists('company_honors');
        Schema::dropIfExists('company_news');
        Schema::dropIfExists('ad_places');
        Schema::dropIfExists('vip_applications');
        Schema::dropIfExists('vip_grades');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['member_type', 'gender', 'qq', 'area', 'company_type', 'credit']);
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['area', 'unit', 'days', 'is_urgent', 'level']);
        });
        Schema::table('supplies', function (Blueprint $table) {
            $table->dropColumn([
                'area', 'min_amount', 'amount', 'days', 'thumb1', 'thumb2',
                'attr1_name', 'attr1_value', 'attr2_name', 'attr2_value', 'attr3_name', 'attr3_value',
                'is_urgent', 'is_elite', 'level',
            ]);
        });
    }
};
