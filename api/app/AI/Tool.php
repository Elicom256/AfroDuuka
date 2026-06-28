<?php

namespace App\AI;

abstract class Tool
{
    abstract public function name(): string;
    abstract public function description(): string;
    abstract public function parameters(): array;
    abstract public function handle(array $parameters): array;
}
