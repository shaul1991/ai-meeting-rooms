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
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('room_id')->index()->comment('rooms 테이블의 id 참조');
            $table->uuid('user_id')->index()->comment('users 테이블의 id 참조');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('status', 20)->default('confirmed');
            $table->integer('total_price');
            $table->string('price_currency', 3)->default('KRW');
            $table->text('purpose')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('cancel_requested_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // 예약 조회 최적화를 위한 인덱스
            $table->index(['room_id', 'start_time', 'end_time']);
            $table->index(['user_id', 'status']);
            $table->index(['status', 'cancel_requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
