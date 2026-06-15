<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // user sudah di-auth via middleware
    }

    public function rules(): array
    {
        $isCv        = $this->input('document_type') === 'cv';
        $isPortfolio = $this->input('document_type') === 'portfolio';
        $hasFile     = $this->hasFile('file');

        return [
            'document_type' => ['required', 'string', 'in:cv,portfolio'],
            'file'          => [
                $isCv ? 'required' : 'nullable',
                'file', 'mimes:pdf', 'max:5120',
            ],
            'portfolio_url' => [
                $isPortfolio && !$hasFile ? 'required' : 'nullable',
                'url', 'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'               => 'File dokumen wajib diunggah.',
            'file.file'                   => 'Upload harus berupa file.',
            'file.mimes'                  => 'File harus berformat PDF.',
            'file.max'                    => 'Ukuran file maksimal 5MB.',
            'document_type.required'      => 'Tipe dokumen wajib dipilih.',
            'document_type.in'            => 'Tipe dokumen tidak valid. Pilih antara cv atau portfolio.',
            'portfolio_url.required'      => 'Link portfolio wajib diisi jika tidak upload file.',
            'portfolio_url.url'           => 'Format link portfolio tidak valid.',
            'portfolio_url.max'           => 'Link portfolio tidak boleh lebih dari 2048 karakter.',
        ];
    }
}
