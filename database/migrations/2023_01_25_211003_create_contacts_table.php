<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 250);
            $table->string('email', 250);
            $table->string('subject', 250);
            $table->string('content', 500);
            $table->timestamps();
            $table->string('honeypot', 250)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->ipAddress('visitor')->nullable();
            $table->boolean('spam_confirmed')->nullable();
            $table->integer('form_time')->nullable();
            $table->timestamp('reported_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contacts');
    }
};
