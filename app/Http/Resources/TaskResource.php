<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'description' => $this->when(! $request->routeIs('api.tasks.index') && ! $request->routeIs('api.tasks.trashed') && ! $request->routeIs('api.tasks.admin'), function () {
                return $this->description;
            }),
            'status' => $this->status,
            'author' => $this->author->name,
            'user' => $this->user->name ?? 'Not assigned',
            'project' => $this->when(! $request->routeIs('api.tasks.trashed'), function () {
                return new ProjectResource($this->whenLoaded('project'));
            }),
            'updated_at' => $this->when(! $request->routeIs('api.tasks.trashed'), function () {
                return new DateTimeResource($this->updated_at);
            }),
            'deleted_at' => $this->when($request->routeIs('api.tasks.trashed'), function () {
                return new DateTimeResource($this->deleted_at);
            }),
            'attachments' => $this->when(! $request->routeIs('api.tasks.index') && ! $request->routeIs('api.tasks.trashed'), function () {
                return MediaResource::collection($this->getMedia('attachments'));
            }),
        ];
    }
}
