<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('darauf_verification_methods', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('controller');
            $table->string('type');
            $table->string('public_key');
            $table->timestamps();

            $table->foreign('controller')
                ->references('did')
                ->on('darauf_did_documents')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('darauf_verification_methods');
    }
};
