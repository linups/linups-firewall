<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banned_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 46);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('banned_ips');
    }
};
