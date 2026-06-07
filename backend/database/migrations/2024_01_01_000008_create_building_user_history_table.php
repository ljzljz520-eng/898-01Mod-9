<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('building_user_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('building_id')->constrained()->onDelete('cascade');
            $table->string('unit_number', 50)->nullable()->comment('房间号');
            $table->enum('resident_type', ['owner', 'tenant', 'committee'])->nullable()
                ->comment('住户类型');
            $table->timestamp('move_in_at')->nullable()->comment('入住时间');
            $table->timestamp('move_out_at')->nullable()->comment('搬出时间');
            $table->text('remark')->nullable()->comment('备注');
            $table->timestamps();

            $table->index('user_id');
            $table->index('building_id');
            $table->index('move_out_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('building_user_history');
    }
};
