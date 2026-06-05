<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 200);
            $table->text('content');
            $table->string('category', 50)->default('general');
            $table->integer('view_count')->default(0);
            $table->integer('reply_count')->default(0);
            $table->tinyInteger('is_pinned')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1:正常 0:删除');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('category');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};
