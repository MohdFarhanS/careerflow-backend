<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya pemilik lamaran yang boleh mengubah catatan.
        $application = $this->route('application');

        return $application && $application->user_id === auth()->id();
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.string' => 'Catatan harus berupa teks.',
        ];
    }
}
