<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('products')->where('status','published')->where('stock',0)->update(['status'=>'sold']);
    }

    public function down(): void
    {
        // Existing sold products cannot be distinguished from rows updated here.
    }
};
