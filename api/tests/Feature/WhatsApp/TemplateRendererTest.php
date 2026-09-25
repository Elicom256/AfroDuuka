<?php

namespace Tests\Feature\WhatsApp;

use App\Exceptions\MissingTemplateVariableException;
use App\Models\NotificationDelivery;
use App\Services\Notifications\TemplateRenderer;
use Tests\TestCase;

class TemplateRendererTest extends TestCase
{
    private TemplateRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new TemplateRenderer;
    }

    public function test_it_substitutes_in_the_order_the_template_declares(): void
    {
        $rendered = $this->renderer->render(
            'Order {{quantity}} of {{product}} for {{branch}}.',
            ['quantity', 'product', 'branch'],
            [
                'branch' => 'Nakasero',
                'product' => 'Sugar 50kg',
                'quantity' => 12,
            ]
        );

        $this->assertSame('Order 12 of Sugar 50kg for Nakasero.', $rendered->body);
    }

    public function test_parameter_order_comes_from_the_template_not_the_data_array(): void
    {
        // The data arrives in an arbitrary order. Meta's {{1}}..{{n}} contract does not.
        $rendered = $this->renderer->render(
            '{{a}} then {{b}}',
            ['a', 'b'],
            ['b' => 'second', 'a' => 'first']
        );

        $this->assertSame(['first', 'second'], $rendered->orderedValues());
        $this->assertSame('first then second', $rendered->body);
    }

    public function test_it_renders_the_variable_names_as_names_not_as_values(): void
    {
        // Regression for B6: the old path fed the `variables` column in as data, so
        // {{product_name}} rendered as the literal text "product_name".
        $rendered = $this->renderer->render(
            'Stock alert for *{{product_name}}*: only {{current_stock}} left.',
            ['product_name', 'current_stock'],
            ['product_name' => 'Rice 25kg', 'current_stock' => 4]
        );

        $this->assertSame('Stock alert for *Rice 25kg*: only 4 left.', $rendered->body);
        $this->assertStringNotContainsString('product_name', $rendered->body);
        $this->assertStringNotContainsString('current_stock', $rendered->body);
    }

    public function test_a_declared_variable_with_no_value_throws(): void
    {
        $this->expectException(MissingTemplateVariableException::class);
        $this->expectExceptionMessage('no value was supplied');

        $this->renderer->render('Hi {{name}}, balance {{amount}}.', ['name', 'amount'], ['name' => 'Ana']);
    }

    public function test_a_placeholder_the_template_never_declared_throws(): void
    {
        // This one would otherwise sail through and reach a customer as {{phone}}.
        $this->expectException(MissingTemplateVariableException::class);
        $this->expectExceptionMessage('does not declare it');

        $this->renderer->render('Hi {{name}}, call {{phone}}.', ['name'], ['name' => 'Ana']);
    }

    public function test_a_null_value_renders_as_empty_rather_than_the_word_null(): void
    {
        $rendered = $this->renderer->render('Ends {{date}}.', ['date'], ['date' => null]);

        $this->assertSame('Ends .', $rendered->body);
    }

    public function test_it_flattens_newlines_because_meta_rejects_them(): void
    {
        $rendered = $this->renderer->render(
            'Items: {{items}}',
            ['items'],
            ['items' => "Sugar\nSalt"]
        );

        $this->assertSame('Items: Sugar Salt', $rendered->body);
    }

    public function test_it_refuses_to_render_an_array_into_a_message(): void
    {
        $this->expectException(MissingTemplateVariableException::class);
        $this->expectExceptionMessage('cannot be rendered');

        $this->renderer->render('Items: {{items}}', ['items'], ['items' => ['a', 'b']]);
    }

    public function test_it_renders_booleans_dates_and_enums_readably(): void
    {
        $rendered = $this->renderer->render(
            '{{paid}} on {{when}}: {{status}}',
            ['paid', 'when', 'status'],
            [
                'paid' => false,
                'when' => new \DateTimeImmutable('2026-09-30'),
                'status' => NotificationDelivery::STATUS_SENT,
            ]
        );

        $this->assertSame('No on 30 Sep 2026: sent', $rendered->body);
    }

    public function test_a_template_with_no_variables_renders_verbatim(): void
    {
        $rendered = $this->renderer->render('Your report is ready.', [], []);

        $this->assertSame('Your report is ready.', $rendered->body);
        $this->assertSame([], $rendered->orderedValues());
    }
}
