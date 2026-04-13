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
        Schema::create('attachments', function (Blueprint $table) {
            // identita
            $table->id();
            $table->ulid('public_id')->unique();
            $table->morphs('attachable');

            $table->string('collection', 32)->default('attachment'); // attachment / profile_photo
            $table->enum('visibility', ['public', 'private'])->default('private');

            // umiestnenie
            $table->string('disk', 64)->default('local'); // public / local
            $table->string('path')->unique();

            // metadata
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
