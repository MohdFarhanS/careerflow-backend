<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');          // nama file asli: "CV_John.pdf"
            $table->string('file_path');          // path di storage: "documents/user_1/..."
            $table->string('document_type');      // "cv" atau "portfolio"
            $table->unsignedBigInteger('file_size'); // ukuran file dalam bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};