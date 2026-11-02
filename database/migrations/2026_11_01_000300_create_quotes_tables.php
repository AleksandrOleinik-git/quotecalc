<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quotes keep a full snapshot of names and computed prices, so editing or deleting
 * catalogue entries later never changes a quote that was already issued.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->string('status', 16)->default('new')->index();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_company')->nullable();
            $table->text('notes')->nullable();
            $table->string('currency', 3);
            $table->boolean('include_vat');
            $table->unsignedInteger('vat_rate_bps');
            $table->unsignedBigInteger('net_cents');
            $table->unsignedBigInteger('vat_cents');
            $table->unsignedBigInteger('total_cents');
            $table->string('source', 16)->default('web');
            $table->timestamps();
        });

        Schema::create('quote_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('material_name');
            $table->json('option_names');
            $table->unsignedInteger('width_mm');
            $table->unsignedInteger('height_mm');
            $table->unsignedInteger('quantity');
            $table->json('breakdown');
            $table->unsignedBigInteger('unit_net_cents');
            $table->unsignedBigInteger('net_cents');
            $table->unsignedBigInteger('vat_cents');
            $table->unsignedBigInteger('total_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
