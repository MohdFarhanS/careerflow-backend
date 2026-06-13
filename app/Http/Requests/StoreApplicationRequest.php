<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'position'     => ['required', 'string', 'max:255'],
            'location'     => ['nullable', 'string', 'max:255'],
            'job_url'      => ['nullable', 'url', 'max:2048'],
            'applied_date' => ['required', 'date'],
            'salary_range' => ['nullable', 'string', 'max:100'],
            'status'       => [
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
            'position.required'     => 'Posisi wajib diisi.',
            'applied_date.required' => 'Tanggal melamar wajib diisi.',
            'applied_date.date'     => 'Format tanggal tidak valid.',
            'job_url.url'           => 'Format URL tidak valid.',
            'status.required'       => 'Status wajib dipilih.',
            'status.in'             => 'Status tidak valid.',
        ];
    }
}