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
            'description' => $this->when(! $request->routeIs('tasks.index') || ! $request->routeIs('tasks.trashed'), function(){
                return $this->description;
            }),
            'status' => $this->status,
            'author' => $this->author->name,
            'user' => $this->user->name ?? 'Not assigned',
            'project' => $this->when(! $request->routeIs('tasks.trashed'), function () {
                new ProjectResource($this->whenLoaded('project'));
            }),
        ];
    }
}
