<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('building_id')->nullable()->after('status')
                ->constrained()->onDelete('set null');
            $table->string('unit_number', 50)->nullable()->after('building_id')
                ->comment('房间号，如 1001');
            $table->enum('verification_status', ['unverified', 'pending', 'verified', 'rejected'])
                ->default('unverified')->after('unit_number')
                ->comment('认证状态：未认证、审核中、已认证、已拒绝');
            $table->enum('resident_type', ['owner', 'tenant', 'committee'])
                ->nullable()->after('verification_status')
                ->comment('住户类型：业主、租户、业委会');
            $table->string('real_name', 50)->nullable()->after('resident_type')
                ->comment('真实姓名');
            $table->string('id_card', 50)->nullable()->after('real_name')
                ->comment('身份证号（加密存储）');
            $table->text('verification_documents')->nullable()->after('id_card')
                ->comment('认证材料（JSON格式，存储图片路径）');
            $table->timestamp('verified_at')->nullable()->after('verification_documents')
                ->comment('认证通过时间');
            $table->timestamp('moved_at')->nullable()->after('verified_at')
                ->comment('搬离时间，有值表示已搬离');
            $table->text('verification_remark')->nullable()->after('moved_at')
                ->comment('审核备注');

            $table->index('building_id');
            $table->index('verification_status');
            $table->index('resident_type');
            $table->index('moved_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['building_id']);
            $table->dropColumn([
                'building_id',
                'unit_number',
                'verification_status',
                'resident_type',
                'real_name',
                'id_card',
                'verification_documents',
                'verified_at',
                'moved_at',
                'verification_remark',
            ]);
        });
    }
};
