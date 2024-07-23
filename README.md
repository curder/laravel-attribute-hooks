# Laravel Attribute hooks

[![Check & fix styling](https://github.com/curder/laravel-attribute-hooks/actions/workflows/pint.yml/badge.svg?branch=master)](https://github.com/curder/laravel-attribute-hooks/actions/workflows/pint.yml)
[![Test Laravel Github action](https://github.com/curder/laravel-attribute-hooks/actions/workflows/run-test.yml/badge.svg?branch=master)](https://github.com/curder/laravel-attribute-hooks/actions/workflows/run-test.yml)

今天在 [x.com](https://x.com/tonysmdev/status/1815576767014338561) 上看到了 Laravel 的 Attribute hooks，感觉非常实用，于是记录一下。

## Model

```php
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
```

## AttributeHooks

```php
<?php

namespace App\Concerns;

use ReflectionClass;
use App\Attributes\BeforeCreate;
use App\Attributes\AfterSaveCommit;
use Illuminate\Database\Eloquent\Model;

trait AttributeHooks
{
    public static function bootAttributeHooks(): void
    {
        static::creating(function (Model $model) {
            static::handleHook($model, BeforeCreate::class);
        });

        static::saved(function (Model $model) {
            static::handleHook($model, AfterSaveCommit::class);
        });
    }

    protected static function handleHook(Model $model, $class): void
    {
        foreach (static::resolveCustomAttributes($class) as $method) {
            if (method_exists($model, $method)) {
                $model->$method();
            }
        }
    }

    private static function resolveCustomAttributes($class): array
    {
        $reflectionClass = new ReflectionClass(static::class);

        return collect($reflectionClass->getAttributes($class))
            ->map(fn ($attribute) => $attribute->getArguments())
            ->flatten()
            ->all();
    }
}
```

## Tests

测试使用到了日志 [`timacdonald/log-fake`](https://github.com/timacdonald/log-fake) 扩展包。

```php
<?php

use App\Models\Post;
use TiMacDonald\Log\LogFake;
use TiMacDonald\Log\LogEntry;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('can call creating event', function () {
    $post = Post::factory()->create();

    expect($post)->slug->toEqual(str($post->title)->slug()->toString());
});

it('can call saved event', function () {
    LogFake::bind();

    $post = Post::factory()->create();

    $post->title = 'Updated Title';
    $post->save();
    Log::assertLogged(fn (LogEntry $log) => $log->level === 'debug'
        && $log->message === 'Post was updated'
    );

    $post->delete();
    $post->title = 'Updated Title again';
    $post->save();
    Log::assertLogged(fn (LogEntry $log) => $log->level === 'debug'
        && $log->message === 'Post was trashed'
    );
});
```

更多变更[查看提交历史](https://github.com/curder/laravel-attribute-hooks/commit/b3b361d7316fd1b9553700f35d8a571c7a007950)

## Preview

![Before](https://github.com/user-attachments/assets/0a8033bd-c9dd-43f6-b531-d5236d34c6b3)

![After](https://github.com/user-attachments/assets/2f9be5d0-c7f3-4d6a-a85b-9f9586fbae9d)

