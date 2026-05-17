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
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('url')->unique();
            $table->unsignedSmallInteger('check_interval')->default(5); // minutes
            $table->unsignedSmallInteger('threshold')->default(3);      
            $table->enum('status', ['pending', 'up', 'down'])->default('pending');
            $table->unsignedInteger('consecutive_failures')->default(0); // tracks streak toward threshold
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
