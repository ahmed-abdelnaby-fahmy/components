<?php

declare(strict_types=1);

namespace Hypervel\Tests\Validation\fixtures;

use Hypervel\Support\Contracts\Arrayable;

class Values implements Arrayable
{
    public function toArray(): array
    {
        return [1, 2, 3, 4];
    }
}
