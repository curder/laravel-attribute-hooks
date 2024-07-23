<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected static function booted(): void
    {
        self::creating(function (Post $model) {
            $model->normalizeTitle();
            $model->generateSlug();
        });

        self::saved(function (Post $model) {
            $model->recordEvent();
        });
    }

    protected function normalizeTitle(): void
    {
        $this->title && $this->title = (string) str($this->title)->title();
    }

    protected function generateSlug(): void
    {
        $this->slug ??= (string) str($this->title)->slug();
    }

    protected function recordEvent(): void
    {
        if ($this->trashed()) {
            $this->recordTrashed();
        } elseif ($this->wasChanged()) {
            $this->recordUpdate();
        }
    }

    protected function recordTrashed(): void
    {
        logger('Post was trashed');
    }

    protected function recordUpdate(): void
    {
        logger('Post was updated');
    }
}
