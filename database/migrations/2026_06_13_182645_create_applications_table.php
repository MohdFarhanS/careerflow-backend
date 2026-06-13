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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('company_name');
            $table->string('position');
            $table->string('location')->nullable();
            $table->string('job_url')->nullable();
            $table->date('applied_date');
            $table->string('salary_range')->nullable();
            $table->enum('status', [
                'Applied',
                'Screening',
                'Technical Test',
                'Interview',
                'Offered',
                'Rejected'
            ])->default('Applied');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
