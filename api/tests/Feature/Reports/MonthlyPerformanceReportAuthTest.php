<?php

namespace Tests\Feature\Reports;

use Tests\TestCase;

/**
 * The report download is a tenant document.
 *
 * Kept apart from MonthlyPerformanceReportTest because that class authenticates in
 * setUp(), and Sanctum::actingAs(null) is not a way to undo it — it crashes inside
 * Sanctum trying to attach an access token to a null user. Asserting "unauthenticated"
 * has to mean genuinely never having authenticated.
 *
 * Both requests below ask for JSON, which is the only way to get a 401 out of this
 * application's API routes. A plain browser GET that fails `auth:sanctum` throws an
 * AuthenticationException looking for a `login` route to redirect to, which does not
 * exist in an API-only app, so it surfaces as a 500. That is pre-existing behaviour for
 * every route in routes/reports.php and is not specific to this one; it is why the PDF
 * download is requested with an Accept header rather than by navigating to it directly.
 */
class MonthlyPerformanceReportAuthTest extends TestCase
{
    public function test_the_figures_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/reports/monthly-performance')->assertUnauthorized();
    }

    public function test_the_pdf_endpoint_requires_authentication(): void
    {
        $this->get('/api/reports/monthly-performance/pdf', ['Accept' => 'application/json'])
            ->assertUnauthorized();
    }
}
