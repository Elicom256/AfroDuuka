<?php

namespace App\AI\Tools;

use App\AI\Tool;

class ExpiringProducts extends Tool
{
    public function name(): string
    {
        return 'expiring_products';
    }

    public function description(): string
    {
        return 'Get products that are expiring soon';
    }

    public function parameters(): array
    {
        return [
            'days' => [
                'type' => 'integer',
                'description' => 'Number of days to look ahead (default 30)',
            ],
        ];
    }

    public function handle(array $parameters): array
    {
        return [
            'message' => 'The current database schema does not have an expiry_date field on products. ' .
                'Expiring products tracking is not available until an expiry date column is added to the products or branch_products table.',
            'supported' => false,
        ];
    }
}
