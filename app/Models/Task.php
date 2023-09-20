<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Task extends Model implements Hasmedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = ['title', 'description', 'author_id', 'user_id', 'project_id', 'status'];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('tasks')
            ->acceptsMimeTypes(['image/jpg', 'image/jpeg', 'image/png'])
            ->onlyKeepLatest(3);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10);
    }
}
