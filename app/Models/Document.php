<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'file_name',
        'file_path',
        'document_type',
        'file_size',
        'portfolio_url',
    ];

    // user_id TIDAK di fillable — di-set via relationship (sama seperti Application)

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}