<?php

namespace App\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class BeforeCreate
{
    /**
     * Create a new attribute instance.
     *
     * @return void
     */
    public function __construct(array|string $classes) {}
}
