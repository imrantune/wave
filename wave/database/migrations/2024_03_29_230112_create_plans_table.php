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
        Schema::create('plans', function (Blueprint $table) {
            $table->increments('id'); // Unsigned integer primary key
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('features');
            $table->string('monthly_price_id')->nullable();
            $table->string('yearly_price_id')->nullable();
            $table->string('onetime_price_id')->nullable();
            $table->boolean('active')->default(1);
            $table->unsignedBigInteger('role_id'); // Unsigned bigint for the foreign key
            $table->boolean('default')->default(0);
            $table->string('monthly_price')->nullable();
            $table->string('yearly_price')->nullable();
            $table->string('onetime_price')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

