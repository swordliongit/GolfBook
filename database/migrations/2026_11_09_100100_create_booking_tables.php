<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->char('country', 2);
            // Only the SHA-256 hash of the API key is stored, never the key itself.
            $table->char('api_key_hash', 64)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();
            $table->string('status', 16)->index();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hotel_id')->constrained()->restrictOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->char('customer_country', 2);
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('guests');
            $table->unsignedInteger('hotel_total_cents');
            $table->unsignedInteger('golf_total_cents');
            $table->unsignedInteger('transfer_total_cents');
            $table->unsignedInteger('total_cents');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['check_in', 'status']);
        });

        Schema::create('booking_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tee_time_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('players');
            $table->unsignedInteger('green_fee_total_cents');
            $table->timestamps();

            $table->unique(['booking_id', 'tee_time_id']);
        });

        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 16);
            $table->string('flight_number', 16)->nullable();
            $table->dateTime('pickup_at')->index();
            $table->unsignedTinyInteger('passengers');
            $table->unsignedInteger('price_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('booking_rounds');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('partners');
    }
};
