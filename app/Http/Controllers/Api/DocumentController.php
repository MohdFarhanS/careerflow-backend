<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService)
    {
    }

    /**
     * GET /api/documents
     * Ambil semua dokumen milik user.
     */
    public function index(): AnonymousResourceCollection
    {
        $documents = $this->documentService->getAll();

        return DocumentResource::collection($documents);
    }

    /**
     * POST /api/documents
     * Upload dokumen baru (replace jika tipe sama sudah ada).
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->documentService->upload(
            file: $request->file('file'),
            documentType: $request->validated('document_type'),
            portfolioUrl: $request->validated('portfolio_url'),
        );

        return response()->json([
            'message'  => 'Dokumen berhasil diunggah.',
            'document' => new DocumentResource($document),
        ], 201);
    }

    /**
     * DELETE /api/documents/{document}
     * Hapus dokumen beserta file fisiknya.
     */
    public function destroy(Document $document): JsonResponse
    {
        // Otorisasi manual — pastikan dokumen milik user yang sedang login
        if ($document->user_id !== auth()->id()) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $this->documentService->delete($document);

        return response()->json(['message' => 'Dokumen berhasil dihapus.']);
    }
}