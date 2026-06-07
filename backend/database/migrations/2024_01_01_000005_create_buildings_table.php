<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('楼栋名称，如 1号楼、A栋');
            $table->string('community_name', 100)->nullable()->comment('小区名称');
            $table->integer('total_floors')->nullable()->comment('总楼层数');
            $table->integer('total_units')->nullable()->comment('总户数');
            $table->tinyInteger('status')->default(1)->comment('1:启用 0:禁用');
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buildings');
    }
};
