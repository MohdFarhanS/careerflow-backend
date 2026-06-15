<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->unsignedBigInteger('file_size')->nullable()->change();
            $table->string('portfolio_url', 2048)->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('portfolio_url');
            // file_path dan file_size dibiarkan nullable — tidak aman dikembalikan ke NOT NULL
            // karena mungkin sudah ada record dengan nilai NULL (dokumen portfolio berbasis URL)
        });
    }
};
