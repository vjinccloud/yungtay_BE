<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('guard_name')->default('admin')->change();
            $table->text('description')->nullable()->after('name')->comment('角色描述');
            $table->boolean('is_active')->default(true)->after('description')->comment('啟用狀態');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('is_active');
        });
    }
};
