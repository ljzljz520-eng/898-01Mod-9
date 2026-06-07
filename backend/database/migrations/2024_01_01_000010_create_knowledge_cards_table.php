<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->onDelete('cascade');
            $table->foreignId('moderator_id')->constrained('users')->onDelete('cascade');
            $table->string('title', 200);
            $table->text('summary');
            $table->string('category', 50)->comment('broadband:宽带办理 school:学区材料 parking:停车证 renovation:装修流程');
            $table->string('tags', 500)->nullable();
            $table->date('expire_date')->nullable();
            $table->date('last_reviewed_at')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1:正常 2:待复核 3:已过期 0:禁用');
            $table->integer('view_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('status');
            $table->index('expire_date');
            $table->index('created_at');
            $table->index(['category', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_cards');
    }
};
