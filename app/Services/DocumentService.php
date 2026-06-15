<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    /**
     * Ambil semua dokumen milik user yang sedang login.
     */
    public function getAll(): \Illuminate\Database\Eloquent\Collection
    {
        return Auth::user()
            ->documents()
            ->latest()
            ->get();
    }

    /**
     * Upload dokumen baru atau simpan via link portfolio.
     * Jika user sudah punya dokumen dengan tipe yang sama, lama dihapus (replace).
     */
    public function upload(?UploadedFile $file, string $documentType, ?string $portfolioUrl = null): Document
    {
        $existing = Auth::user()
            ->documents()
            ->where('document_type', $documentType)
            ->first();

        if ($existing) {
            $this->deleteFile($existing);
            $existing->delete();
        }

        if ($file) {
            $folder = 'documents/' . Auth::id();
            $path   = $file->store($folder, 'public');

            return Auth::user()->documents()->create([
                'file_name'     => $file->getClientOriginalName(),
                'file_path'     => $path,
                'document_type' => $documentType,
                'file_size'     => $file->getSize(),
                'portfolio_url' => null,
            ]);
        }

        return Auth::user()->documents()->create([
            'file_name'     => null,
            'file_path'     => null,
            'document_type' => $documentType,
            'file_size'     => null,
            'portfolio_url' => $portfolioUrl,
        ]);
    }

    /**
     * Hapus dokumen beserta file fisiknya jika ada.
     */
    public function delete(Document $document): void
    {
        $this->deleteFile($document);
        $document->delete();
    }

    /**
     * Hapus file fisik dari storage jika ada.
     */
    private function deleteFile(Document $document): void
    {
        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }
    }
}
