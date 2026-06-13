<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
            $query->where('location', 'like', "%{$location}%");
        }

        return $query;
    }

    /**
     * Cari berdasarkan company name atau posisi.
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('company_name', 'like', "%{$keyword}%")
                  ->orWhere('position', 'like', "%{$keyword}%");
            });
        }

        return $query;
    }
}