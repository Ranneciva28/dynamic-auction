<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void {
    Schema::create('categories',function(Blueprint $t){$t->id();$t->string('name');$t->string('slug')->unique();$t->timestamps();});
    Schema::create('products',function(Blueprint $t){$t->id();$t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();$t->string('name');$t->string('slug')->unique();$t->text('description')->nullable();$t->text('condition_notes')->nullable();$t->unsignedBigInteger('price');$t->unsignedBigInteger('market_price')->nullable();$t->unsignedInteger('stock')->default(0);$t->string('status')->default('draft')->index();$t->string('cover_path')->nullable();$t->integer('sort_order')->default(0);$t->timestamps();$t->index(['status','sort_order']);});
    Schema::create('product_images',function(Blueprint $t){$t->id();$t->foreignId('product_id')->constrained()->cascadeOnDelete();$t->string('path');});
    Schema::create('orders',function(Blueprint $t){$t->id();$t->string('code')->unique();$t->string('public_token',64)->unique();$t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();$t->string('customer_name');$t->string('customer_phone');$t->string('customer_email')->nullable();$t->unsignedInteger('quantity');$t->unsignedBigInteger('unit_price');$t->unsignedBigInteger('total');$t->string('status')->default('pending')->index();$t->string('proof_path')->nullable();$t->text('customer_note')->nullable();$t->text('admin_note')->nullable();$t->timestamp('paid_at')->nullable();$t->timestamps();});
    Schema::create('settings',function(Blueprint $t){$t->id();$t->string('key')->unique();$t->text('value')->nullable();$t->timestamps();});
} public function down(): void {Schema::dropIfExists('settings');Schema::dropIfExists('orders');Schema::dropIfExists('product_images');Schema::dropIfExists('products');Schema::dropIfExists('categories');} };
