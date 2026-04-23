<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('source_code', 10)->nullable()->index()
                  ->after('name')
                  ->comment('外部來源代碼（如 CNA: PD, ED, JD）');
            
            $table->string('source_provider', 50)->nullable()
                  ->after('source_code')
                  ->comment('來源提供者（如 cna, reuters）');
            
            // 複合索引，支援多個來源的分類代碼
            $table->index(['source_provider', 'source_code'], 'categories_source_index');
        });
    }

    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_source_index');
            $table->dropIndex(['source_code']);
            $table->dropColumn(['source_code', 'source_provider']);
        });
    }
};
