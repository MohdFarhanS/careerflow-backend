<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Untuk update, kita cek kepemilikan via interview → application → user
        // Interview tidak punya user_id langsung, jadi kita traverse relasi.
        $interview = $this->route('interview');

        return $interview
            && $interview->application
            && $interview->application->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            // application_id tidak boleh diubah setelah dibuat
            'interview_date'  => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $interview = $this->route('interview');
                    $appliedDate = $interview?->application?->applied_date;
                    if ($appliedDate && $value < $appliedDate->toDateString()) {
                        $fail('Tanggal interview tidak boleh sebelum tanggal lamaran ('
                            . $appliedDate->translatedFormat('d F Y')
                            . ').');
                    }
                },
            ],
            'interview_time'  => ['required', 'date_format:H:i'],
            'interview_type'  => ['required', 'in:Online,Offline'],
            'meeting_url'     => ['nullable', 'url', 'max:2048'],
            'notes'           => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'interview_date.required'  => 'Tanggal interview wajib diisi.',
            'interview_date.date'      => 'Format tanggal tidak valid.',
            'interview_time.required'  => 'Jam interview wajib diisi.',
            'interview_time.date_format' => 'Format jam harus HH:MM.',
            'interview_type.required'  => 'Tipe interview wajib dipilih.',
            'interview_type.in'        => 'Tipe interview harus Online atau Offline.',
            'meeting_url.url'          => 'Format URL meeting tidak valid.',
        ];
    }
}