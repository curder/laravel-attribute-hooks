<?php

namespace App\Models;

use App\Attributes\BeforeCreate;
use App\Concerns\AttributeHooks;
use App\Attributes\AfterSaveCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[BeforeCreate('normalizeTitle', 'generateSlug')]
#[AfterSaveCommit('recordEvent')]
class Post extends Model
{
    use AttributeHooks, HasFactory, SoftDeletes;

    protected $guarded = [];

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
