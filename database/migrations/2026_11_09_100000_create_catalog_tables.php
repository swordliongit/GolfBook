<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('region', 64)->index();
            $table->unsignedTinyInteger('stars');
            // Price per person per night, stored in euro cents to avoid float rounding.
            $table->unsignedInteger('nightly_rate_cents');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('golf_courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('region', 64)->index();
            $table->unsignedTinyInteger('holes')->default(18);
            $table->unsignedTinyInteger('par')->default(72);
            $table->unsignedInteger('green_fee_cents');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            // 1.00 = base price, 1.35 = +35 % in high season.
            $table->decimal('multiplier', 4, 2);
            $table->timestamps();

            $table->index(['starts_on', 'ends_on']);
        });

        Schema::create('tee_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('golf_course_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->unsignedTinyInteger('capacity')->default(4);
            // Denormalised counter, only changed inside a transaction with a row lock.
            $table->unsignedTinyInteger('booked_players')->default(0);
            $table->timestamps();

            $table->unique(['golf_course_id', 'starts_at']);
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tee_times');
        Schema::dropIfExists('seasons');
        Schema::dropIfExists('golf_courses');
        Schema::dropIfExists('hotels');
    }
};
