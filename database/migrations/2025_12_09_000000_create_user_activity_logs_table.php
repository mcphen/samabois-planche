<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('method', 10)->nullable();
            $table->text('url')->nullable();
            $table->string('route_name')->nullable();
            $table->string('controller')->nullable();
            $table->string('action')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->mediumText('request_body')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'performed_at']);
            $table->index(['route_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
