<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        // Simpan file baru lebih dulu. Filesystem tidak transaksional, jadi
        // operasi store dilakukan di luar DB::transaction agar bisa di-cleanup
        // manual bila penulisan database gagal (mencegah file orphan).
        $newPath = $file
            ? $file->store('documents/' . Auth::id(), 'public')
            : null;

        try {
            $result = DB::transaction(function () use ($file, $newPath, $documentType, $portfolioUrl) {
                $existing = Auth::user()
                    ->documents()
                    ->where('document_type', $documentType)
                    ->first();

                // Hapus record lama; file fisiknya baru dihapus setelah commit sukses.
                if ($existing) {
                    $existing->delete();
                }

                $document = Auth::user()->documents()->create([
                    'file_name'     => $file?->getClientOriginalName(),
                    'file_path'     => $newPath,
                    'document_type' => $documentType,
                    'file_size'     => $file?->getSize(),
                    'portfolio_url' => $file ? null : $portfolioUrl,
                ]);

                return ['document' => $document, 'existing' => $existing];
            });
        } catch (\Throwable $e) {
            // DB gagal → buang file baru yang sudah terlanjur tersimpan.
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $e;
        }

        // Commit sukses → baru hapus file fisik milik record lama (jika ada).
        if ($result['existing']) {
            $this->deleteFile($result['existing']);
        }

        return $result['document'];
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
