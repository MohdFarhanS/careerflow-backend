<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'application_id'   => $this->application_id,

            // Kita sertakan data aplikasi (eager loaded) supaya frontend
            // bisa tampilkan "PT ABC — Backend Engineer" di kartu interview
            // tanpa request tambahan ke /api/applications/{id}
            'company_name'     => $this->whenLoaded('application', fn() => $this->application->company_name),
            'position'         => $this->whenLoaded('application', fn() => $this->application->position),
            'applied_date'     => $this->whenLoaded('application', fn() => $this->application->applied_date?->toDateString()),

            'interview_date'   => $this->interview_date->format('Y-m-d'),
            'interview_time'   => $this->interview_time,
            'interview_type'   => $this->interview_type,
            'meeting_url'      => $this->meeting_url,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}