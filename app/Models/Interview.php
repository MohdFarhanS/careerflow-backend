<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'interview_date',
        'interview_time',
        'interview_type',
        'meeting_url',
        'notes',
    ];

    protected $casts = [
        'interview_date' => 'date',
    ];

    /**
     * Interview selalu milik satu Application.
     * Relasi ini dipakai untuk eager load di InterviewService
     * agar kita bisa tampilkan company_name & position di frontend
     * tanpa join manual.
     */
    public function application(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}