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
            $table->string('did_document_did');
            $table->string('controller');
            $table->string('type');
            $table->string('publicKeyMultibase');
            $table->timestamps();

            $table->foreign('did_document_did')
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
