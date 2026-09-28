<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('products',function(Blueprint $table){
            $table->string('source_url')->nullable()->unique();
            $table->timestamp('source_synced_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('products',function(Blueprint $table){$table->dropUnique(['source_url']);$table->dropColumn(['source_url','source_synced_at']);});
    }
};
