<?php

namespace App\AI;

use Gemini\Data\GenerationConfig;
use Gemini\Enums\ResponseMimeType;

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

        // Try Gemini first, fall back to keyword matching
        $intent = $this->classifyWithGemini($prompt);

        if ($intent === null) {
            $intent = $this->classifyWithKeywords($prompt);
        }

        // If Gemini decided no tool is needed (general conversation)
        if ($intent === false) {
            return $this->success(['response' => 'How can I help you with your inventory today? Ask me about products, sales, stock, or any business data.'], 'general');
        }

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

    protected function classifyWithGemini(string $prompt): array|null|false
    {
        $apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');

        if (empty($apiKey)) {
            return null;
        }

        $toolDefinitions = [];
        foreach ($this->registry->all() as $name => $tool) {
            $toolDefinitions[] = [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'parameters' => $tool->parameters(),
            ];
        }

        $systemPrompt = 'You are an inventory management AI assistant for DuukaFlow. Your job is to classify user requests into the correct tool.

Available tools:
' . json_encode($toolDefinitions, JSON_PRETTY_PRINT) . '

Rules:
1. Match the user\'s request to the most relevant tool.
2. Extract the necessary parameters for that tool from the request.
3. If the user is just greeting or having general conversation (not asking about inventory data), respond with {"tool": null, "message": "a friendly response"}.
4. If the user asks about "expired", "expiring", or "danger zone" products, use the "expiring_products" tool.
5. If the user asks about restocking or "running out", use the "product_search" tool (Stock section) or the restocking logic.
6. Always respond with valid JSON only, no other text.

Response format:
- For tool calls: {"tool": "tool_name", "parameters": {"param1": "value1"}}
- For general chat: {"tool": null, "message": "your friendly response"}
- For unknown: {"tool": null, "message": "I can help you check products, sales, stock levels, revenue, and more. What would you like to know?"}';

        try {
            $client = \Gemini::client($apiKey);

            $response = $client->generativeModel('models/gemini-2.0-flash')
                ->withGenerationConfig(new GenerationConfig(
                    responseMimeType: ResponseMimeType::APPLICATION_JSON,
                    temperature: 0.1,
                ))
                ->generateContent($systemPrompt . "\n\nUser: " . $prompt);

            $text = $response->text();
            $decoded = json_decode($text, true);

            if (!is_array($decoded)) {
                return null;
            }

            // Gemini chose not to use a tool (general conversation)
            if (!isset($decoded['tool']) || $decoded['tool'] === null) {
                return false;
            }

            return [
                'tool' => $decoded['tool'],
                'parameters' => $decoded['parameters'] ?? [],
            ];
        } catch (\Exception $e) {
            // Fall back to keyword matching
            return null;
        }
    }

    public function classifyWithKeywords(string $prompt): array
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
