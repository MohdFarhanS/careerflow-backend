<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'file_name'     => $this->file_name,
            'document_type' => $this->document_type,
            'file_size'     => $this->file_size,
            'file_url'      => $this->file_path ? Storage::disk('public')->url($this->file_path) : null,
            'portfolio_url' => $this->portfolio_url,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}