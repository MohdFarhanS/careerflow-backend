<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Validasi kepemilikan: application_id yang dikirim harus milik
        // user yang sedang login. Cek ini dilakukan di sini (FormRequest)
        // bukan di controller, sesuai pola arsitektur proyek.
        $application = \App\Models\Application::find($this->application_id);

        return $application && $application->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'application_id'  => ['required', 'integer', 'exists:applications,id'],
            'interview_date'  => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    $application = \App\Models\Application::find($this->application_id);
                    if ($application && $value < $application->applied_date->toDateString()) {
                        $fail('Tanggal interview tidak boleh sebelum tanggal lamaran ('
                            . $application->applied_date->translatedFormat('d F Y')
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
            'application_id.required'  => 'Pilih lamaran terlebih dahulu.',
            'application_id.exists'    => 'Lamaran tidak ditemukan.',
            'interview_date.required'  => 'Tanggal interview wajib diisi.',
            'interview_date.date'      => 'Format tanggal tidak valid.',
            'interview_date.after_or_equal' => 'Tanggal interview tidak boleh di masa lalu.',
            'interview_time.required'  => 'Jam interview wajib diisi.',
            'interview_time.date_format' => 'Format jam harus HH:MM.',
            'interview_type.required'  => 'Tipe interview wajib dipilih.',
            'interview_type.in'        => 'Tipe interview harus Online atau Offline.',
            'meeting_url.url'          => 'Format URL meeting tidak valid.',
        ];
    }
}