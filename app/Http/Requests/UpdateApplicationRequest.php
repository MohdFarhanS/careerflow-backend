<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pastikan hanya pemilik yang bisa update
        return $this->route('application')->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'position'     => ['sometimes', 'required', 'string', 'max:255'],
            'location'     => ['nullable', 'string', 'max:255'],
            'job_url'      => ['nullable', 'url', 'max:2048'],
            'applied_date' => ['sometimes', 'required', 'date'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'status'       => [
                'sometimes',
                'required',
                Rule::in(['Applied', 'Screening', 'Technical Test', 'Interview', 'Offered', 'Rejected']),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'Nama perusahaan wajib diisi.',
            'company_name.max'      => 'Nama perusahaan tidak boleh lebih dari 255 karakter.',
            'position.required'     => 'Posisi wajib diisi.',
            'position.max'          => 'Posisi tidak boleh lebih dari 255 karakter.',
            'location.max'          => 'Lokasi tidak boleh lebih dari 255 karakter.',
            'job_url.url'           => 'Format URL tidak valid.',
            'job_url.max'           => 'URL lowongan tidak boleh lebih dari 2048 karakter.',
            'applied_date.required' => 'Tanggal melamar wajib diisi.',
            'applied_date.date'     => 'Format tanggal tidak valid.',
            'salary_range.max'      => 'Kisaran gaji tidak boleh lebih dari 100 karakter.',
            'status.required'       => 'Status wajib dipilih.',
            'status.in'             => 'Status tidak valid.',
        ];
    }
}