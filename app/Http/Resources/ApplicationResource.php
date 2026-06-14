<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\InterviewResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'company_name' => $this->company_name,
            'position'     => $this->position,
            'location'     => $this->location,
            'job_url'      => $this->job_url,
            'applied_date' => $this->applied_date?->toDateString(),
            'salary_range' => $this->salary_range,
            'status'       => $this->status,
            'notes'        => $this->notes,
            'interviews'   => InterviewResource::collection($this->whenLoaded('interviews')),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}