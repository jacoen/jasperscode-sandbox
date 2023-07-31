<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->when($request->routeIs('projects.show'), function () {
                return $this->description;
            }),
            'manager' => $this->manager->name ?? 'Not assigned',
            'status' => $this->status,
            'due_date' => $this->due_date->format('d M Y'),
            'last_updated' => $this->when(! $request->routeIs('projects.trashed'), function () {
                return DateTimeResource::make($this->updated_at);
            }),
            'deleted_at' => $this->when($request->routeIs('projects.trashed'), function () {
                return DateTimeResource::make($this->deleted_at);
            }),
            'is_pinned' => $this->when(Auth::user()->hasRole(['Admin', 'Super Admin']), function () {
                return $this->is_pinned;
            }),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}
