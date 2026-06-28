<?php

namespace App\AI;

class Agent
{
    public function __construct(
        protected ToolRegistry $registry
    ) {}

    public function handle(string $prompt): array
    {
        $prompt = trim($prompt);

        if (empty($prompt)) {
            return $this->error('Please provide a question or request.');
        }

        $intent = $this->classifyIntent($prompt);

        $tool = $this->registry->find($intent['tool']);

        if (!$tool) {
            return $this->error(
                "I couldn't find a tool to handle that request. Available tools: " .
                implode(', ', array_keys($this->registry->all()))
            );
        }

        try {
            $result = $tool->handle($intent['parameters']);
            return $this->success($result, $tool->name());
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function classifyIntent(string $prompt): array
    {
        $lower = strtolower($prompt);

        foreach ($this->registry->all() as $name => $tool) {
            $description = strtolower($tool->description());
            if (str_contains($lower, $name) || str_contains($description, $lower) || $this->fuzzyMatch($lower, $name)) {
                return [
                    'tool' => $name,
                    'parameters' => $this->extractParameters($prompt, $tool),
                ];
            }
        }

        return [
            'tool' => 'product_search',
            'parameters' => ['query' => $prompt],
        ];
    }

    protected function fuzzyMatch(string $input, string $toolName): bool
    {
        $keywords = str_replace('_', ' ', $toolName);
        $words = explode(' ', $keywords);

        $matchCount = 0;
        foreach ($words as $word) {
            if (strlen($word) < 3) {
                continue;
            }
            if (str_contains($input, $word)) {
                $matchCount++;
            }
        }

        return $matchCount >= 2;
    }

    protected function extractParameters(string $prompt, Tool $tool): array
    {
        return ['query' => $prompt];
    }

    protected function success(mixed $data, string $toolName): array
    {
        return [
            'success' => true,
            'tool' => $toolName,
            'data' => $data,
        ];
    }

    protected function error(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }

    public function definitions(): array
    {
        return $this->registry->definitions();
    }
}
