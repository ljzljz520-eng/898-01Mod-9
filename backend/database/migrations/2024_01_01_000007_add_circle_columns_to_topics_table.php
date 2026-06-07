<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->enum('circle_type', ['public', 'building', 'committee', 'tenant'])
                ->default('public')->after('category')
                ->comment('圈层类型：公共广场、楼栋内、业委会、租户');
            $table->foreignId('building_id')->nullable()->after('circle_type')
                ->constrained()->onDelete('set null')
                ->comment('关联楼栋ID，仅楼栋/业委会/租户圈层使用');
            $table->json('extra_fields')->nullable()->after('building_id')
                ->comment('扩展字段，不同分类显示不同字段（JSON格式）');

            $table->index('circle_type');
            $table->index('building_id');
        });
    }

    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropForeign(['building_id']);
            $table->dropColumn(['circle_type', 'building_id', 'extra_fields']);
        });
    }
};
