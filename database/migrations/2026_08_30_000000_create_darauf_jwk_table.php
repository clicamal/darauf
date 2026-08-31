<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('darauf_jwks', function (Blueprint $table) {
            $table->id();
            $table->string('verification_method_id');
            $table->string('kty');
            $table->string('use');
            $table->string('key_ops');
            $table->string('kid');
            $table->string('e');
            $table->string('n');
            $table->string('k');
            $table->timestamps();

            $table->foreign('verification_method_id')
                ->references('id')
                ->on('darauf_verification_methods')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('darauf_jwks');
    }
};
