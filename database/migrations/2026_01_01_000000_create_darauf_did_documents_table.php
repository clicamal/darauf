<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('darauf_did_documents', function (Blueprint $table) {
            $table->id();
            $table->string('did_document_id')->unique();
            $table->text('serialized')->nullable(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('darauf_did_documents');
    }
};
