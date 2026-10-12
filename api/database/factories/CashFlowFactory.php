<?php

namespace Database\Factories;

use App\Enums\CashFlowDirection;
use App\Enums\CashFlowType;
use App\Models\CashFlow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashFlow>
 */
class CashFlowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * This returned an empty array, which every caller worked around by passing a
     * complete row inline — three report tests each rebuilt the same seven fields,
     * and none of them had to supply direction or currency because nothing on the
     * factory required it. A CashFlow created with a null direction is exactly the
     * row the reporting queries cannot aggregate, so the omission hid a real invariant
     * behind a factory that technically "worked".
     *
     * Defaults now describe a valid inflow: a sale, credit direction, in the
     * business's own currency, completed today. Overrides in the callers still win,
     * so the report tests are unchanged in behaviour.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_code' => 'CF-'.str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'type' => CashFlowType::Sale->value,
            'direction' => CashFlowDirection::Credit->value,
            'amount' => $this->faker->randomFloat(2, 1000, 500000),
            'currency' => 'UGX',
            'category' => 'product_sales',
            'status' => 'completed',
            'payment_method' => 'cash',
            'transaction_date' => now()->toDateString(),
            'description' => $this->faker->sentence(),
        ];
    }

    /**
     * An outflow: an expense or a purchase.
     */
    public function debit(string $type = CashFlowType::Expense->value): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'direction' => CashFlowDirection::Debit->value,
            'category' => $type === CashFlowType::Expense->value ? 'operating_expenses' : 'purchases',
        ]);
    }

    /**
     * An inflow that is not a sale.
     */
    public function inflow(string $type = CashFlowType::PaymentIn->value): static
    {
        return $this->state(fn () => [
            'type' => $type,
            'direction' => CashFlowDirection::Credit->value,
            'category' => 'other_income',
        ]);
    }
}
