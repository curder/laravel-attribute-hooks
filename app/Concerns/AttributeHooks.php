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
