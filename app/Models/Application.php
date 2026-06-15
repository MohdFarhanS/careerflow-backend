<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'position',
        'location',
        'job_url',
        'applied_date',
        'salary_range',
        'status',
        'notes',
    ];

    protected $casts = [
        'applied_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Filter berdasarkan status.
     */
    public function scopeByStatus($query, ?string $status)
    {
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Filter berdasarkan lokasi.
     */
    public function scopeByLocation($query, ?string $location)
    {
        if ($location) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $location);
            $query->where('location', 'like', "%{$escaped}%");
        }

        return $query;
    }

    /**
     * Cari berdasarkan company name atau posisi.
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if ($keyword) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $keyword);
            $query->where(function ($q) use ($escaped) {
                $q->where('company_name', 'like', "%{$escaped}%")
                  ->orWhere('position', 'like', "%{$escaped}%");
            });
        }

        return $query;
    }
}