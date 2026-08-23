<?php

declare(strict_types=1);

namespace Contempt\Workflow\Definition;

final readonly class TransitionName implements \Stringable
{
    public function __construct(public string $value)
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:-]{0,127}$/D', $value) !== 1 || str_contains($value, '..')) {
            throw new \InvalidArgumentException('Workflow transition name must be a safe identifier of at most 128 bytes.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
