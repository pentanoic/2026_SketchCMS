<?php
/**
 * Raw - Raw SQL Expression Wrapper
 */

class Raw
{
    private string $expression;

    public function __construct(string $expression)
    {
        $this->expression = $expression;
    }

    public function getValue(): string
    {
        return $this->expression;
    }

    public function __toString(): string
    {
        return $this->expression;
    }
}
