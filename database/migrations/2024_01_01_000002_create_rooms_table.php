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
        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id')->nullable()->index()->comment('room_groups 테이블의 id 참조');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('capacity');
            $table->jsonb('operating_hours')->comment('요일별 운영 시간');
            $table->integer('price_per_slot')->comment('30분당 가격');
            $table->string('price_currency', 3)->default('KRW');
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable()->comment('추가 메타데이터 (시설, 장비 등)');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
