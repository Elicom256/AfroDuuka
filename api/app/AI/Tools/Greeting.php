<?php

namespace App\AI\Tools;

use App\AI\Tool;

class Greeting extends Tool
{
    public function name(): string
    {
        return 'greeting';
    }

    public function description(): string
    {
        return 'Handle user greetings such as hi, hello, hey, good morning, good afternoon, good evening, and howdy';
    }

    public function parameters(): array
    {
        return [
            'message' => [
                'type' => 'string',
                'description' => 'The greeting message from the user',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        $user = request()->user();
        $username = $user?->username ?? 'there';
        $business = $user?->business;
        $businessName = $business?->name ?? 'your business';

        $hour = (int) now()->format('G');

        if ($hour < 12) {
            $timeGreeting = 'Good morning';
        } elseif ($hour < 17) {
            $timeGreeting = 'Good afternoon';
        } else {
            $timeGreeting = 'Good evening';
        }

        $greetings = [
            "{$timeGreeting}, {$username}! Welcome to your inventory command center. How can I help you run {$businessName} today?",
            "Hey {$username}! Ready to track your inventory? I can help with sales, stock levels, purchases, and more.",
            "{$timeGreeting}, {$username}! What would you like to know about {$businessName} today?",
            "Hello {$username}! I'm your DuukaFlow AI assistant. Ask me anything about your inventory, sales, or business performance.",
            "{$timeGreeting}, {$username}! I've got all your business data at my fingertips. What would you like to explore in {$businessName}?",
        ];

        return [
            'response' => $greetings[array_rand($greetings)],
        ];
    }
}
