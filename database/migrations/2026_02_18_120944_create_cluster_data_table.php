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
        Schema::create('cluster_data', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
	    $table->string('duck_id');
	    $table->string('topic');
	    $table->string('message_id');
	    $table->text('payload');
	    $table->text('path')->nullable();
	    $table->integer('hops');
	    $table->integer('duck_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cluster_data');
    }
};
