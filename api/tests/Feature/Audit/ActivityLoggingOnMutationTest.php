<?php

namespace Tests\Feature\Audit;

use App\Models\ActivityLog;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\CashFlow;
use App\Models\EmployeeRemuneration;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FinancialAudit;
use App\Models\Product;
use App\Models\ProductAudit;
use App\Models\ProductAuditItem;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Eleven endpoints write to their record and then call ActivityLog::log().
 *
 * App\Models\ActivityLog extends Spatie's Activity, which has no static log(); that
 * method lives on the unrelated ActivityLogger instance class. So every one of those
 * calls threw a BadMethodCallException *after* its write, and the response never
 * reached the caller.
 *
 * Two shapes of the same fault, and the reported one is the mildest:
 *
 *   - where the write is not in a transaction (expenses, expense categories,
 *     remuneration, the two cancelAudit methods) the row lands and the request
 *     answers 500, which the UI shows as a failure. "Approve expense updates but the
 *     toast says it failed" is this.
 *   - where the write IS in a transaction (ProductAuditService::approveAudit and
 *     FinancialAuditService::approveAudit) the exception rolls the whole thing back.
 *     Approving a product audit therefore adjusted no stock and changed no status, and
 *     approving a financial audit never persisted at all. Neither is in the bug
 *     report.
 *
 * So each test asserts the record survived AND the request answered 200. The 200 is
 * the half that was broken; asserting only the row would have passed throughout.
 */
class ActivityLoggingOnMutationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private BusinessBranch $branch;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::factory()->create();
        $this->branch = BusinessBranch::factory()->create(['business_id' => $this->business->id]);

        $role = Role::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'executive',
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'role_id' => $role->id,
        ]);

        $this->actingAs($this->user);
    }

    private function assertLogged(string $logName): void
    {
        $this->assertTrue(
            ActivityLog::withoutGlobalScopes()->where('log_name', $logName)->exists(),
            "Expected an activity log named [{$logName}] to have been written."
        );
    }

    private function expenseCategory(): ExpenseCategory
    {
        return ExpenseCategory::create([
            'name' => 'Rent',
            'business_id' => $this->business->id,
            'created_by' => $this->user->id,
        ]);
    }

    private function expense(?ExpenseCategory $category = null): Expense
    {
        return Expense::create([
            'expense_category_id' => ($category ?? $this->expenseCategory())->id,
            'amount' => 25000,
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'description' => 'Monthly rent',
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_approving_an_expense_answers_200_and_approves_it(): void
    {
        $expense = $this->expense();

        $this->postJson("/api/expenses/branch-expenses/{$expense->id}/approve")->assertOk();

        $this->assertSame('approved', $expense->fresh()->status);
        $this->assertLogged('approved_expense');
    }

    public function test_updating_an_expense_answers_200(): void
    {
        $expense = $this->expense();

        $this->putJson("/api/expenses/branch-expenses/{$expense->id}", [
            'amount' => 30000,
        ])->assertOk();

        $this->assertEquals(30000, (float) $expense->fresh()->amount);
        $this->assertLogged('updated_expense');
    }

    /**
     * destroy() has no $request parameter, so the broken call also read an undefined
     * variable. Both faults sat on the same line and both fired after the delete.
     */
    public function test_deleting_an_expense_answers_200(): void
    {
        $expense = $this->expense();

        $this->deleteJson("/api/expenses/branch-expenses/{$expense->id}")->assertOk();

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
        $this->assertLogged('deleted_expense');
    }

    public function test_updating_an_expense_category_answers_200(): void
    {
        $category = $this->expenseCategory();

        $this->putJson("/api/expenses/expense-categories/{$category->id}", [
            'name' => 'Office rent',
        ])->assertOk();

        $this->assertSame('Office rent', $category->fresh()->name);
        $this->assertLogged('updated_expense_category');
    }

    public function test_deleting_an_expense_category_answers_200(): void
    {
        $category = $this->expenseCategory();

        $this->deleteJson("/api/expenses/expense-categories/{$category->id}")->assertOk();

        $this->assertSoftDeleted('expense_categories', ['id' => $category->id]);
        $this->assertLogged('deleted_expense_category');
    }

    private function remuneration(): EmployeeRemuneration
    {
        // No WorkerFactory exists, and Worker has no HasFactory. employee_code is
        // globally unique, so it is spelled out rather than generated from a count.
        $worker = Worker::create([
            'user_id' => $this->user->id,
            'employee_code' => 'EMP-'.uniqid(),
            'employment_type' => 'full_time',
            'status' => 'active',
        ]);

        return EmployeeRemuneration::create([
            'worker_id' => $worker->id,
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'amount' => 500000,
            'type' => 'salary',
            'payment_date' => now()->toDateString(),
            'status' => 'pending',
        ]);
    }

    public function test_updating_a_remuneration_record_answers_200(): void
    {
        $remuneration = $this->remuneration();

        $this->putJson("/api/dashboard/employee-remuneration/{$remuneration->id}", [
            'amount' => 550000,
        ])->assertOk();

        $this->assertEquals(550000, (float) $remuneration->fresh()->amount);
        $this->assertLogged('updated_employee_remuneration');
    }

    public function test_deleting_a_remuneration_record_answers_200(): void
    {
        $remuneration = $this->remuneration();

        $this->deleteJson("/api/dashboard/employee-remuneration/{$remuneration->id}")->assertOk();

        $this->assertDatabaseMissing('employee_remunerations', ['id' => $remuneration->id]);
        $this->assertLogged('deleted_employee_remuneration');
    }

    /**
     * The stock adjustment is the whole point of approving a product audit. It ran
     * inside the transaction that the broken log call aborted, so the count was never
     * applied and the status never changed.
     */
    public function test_approving_a_product_audit_applies_the_count_and_sticks(): void
    {
        // A product belongs to a branch; the products table carries no business_id.
        $product = Product::factory()->create([
            'business_branch_id' => $this->branch->id,
            'quantity' => 10,
        ]);

        $audit = ProductAudit::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'performed_by' => $this->user->id,
        ]);

        // Counted 7 against a system figure of 10: three missing.
        ProductAuditItem::create([
            'product_audit_id' => $audit->id,
            'product_id' => $product->id,
            'system_quantity' => 10,
            'counted_quantity' => 7,
            'difference' => -3,
        ]);

        $this->postJson("/api/product-audits/{$audit->id}/approve")->assertOk();

        $this->assertSame('approved', $audit->fresh()->status);
        $this->assertSame($this->user->id, $audit->fresh()->approved_by);
        $this->assertSame(7, $product->fresh()->quantity, 'The counted quantity is what the shelf now holds.');

        $this->assertTrue(
            StockMovement::withoutGlobalScopes()
                ->where('product_id', $product->id)
                ->where('reference_type', ProductAudit::class)
                ->where('reference_id', $audit->id)
                ->exists(),
            'The movement has to name the audit that caused it.'
        );

        $this->assertLogged('approved_product_audit');
    }

    public function test_cancelling_a_product_audit_answers_200(): void
    {
        $audit = ProductAudit::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'performed_by' => $this->user->id,
        ]);

        $this->postJson("/api/product-audits/{$audit->id}/cancel")->assertOk();

        $this->assertSame('cancelled', $audit->fresh()->status);
        $this->assertLogged('cancelled_product_audit');
    }

    /**
     * Same fault as the product audit: the approve path is wrapped in a transaction, so
     * approving a financial audit silently did nothing.
     */
    public function test_approving_a_financial_audit_sticks(): void
    {
        $audit = FinancialAudit::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'performed_by' => $this->user->id,
        ]);

        $this->postJson("/api/financial-audits/{$audit->id}/approve")->assertOk();

        $this->assertSame('approved', $audit->fresh()->status);
        $this->assertSame($this->user->id, $audit->fresh()->approved_by);
        $this->assertNotNull($audit->fresh()->approved_at);

        $this->assertLogged('approved_financial_audit');
    }

    public function test_cancelling_a_financial_audit_answers_200(): void
    {
        $audit = FinancialAudit::factory()->create([
            'business_id' => $this->business->id,
            'business_branch_id' => $this->branch->id,
            'status' => 'completed',
            'performed_by' => $this->user->id,
        ]);

        $this->postJson("/api/financial-audits/{$audit->id}/cancel")->assertOk();

        $this->assertSame('cancelled', $audit->fresh()->status);
        $this->assertLogged('cancelled_financial_audit');
    }

    /**
     * Guards the reason the fault survived so long: these writes were never in a test,
     * and the only thing that made them visible was the error toast, not a broken
     * record. An expense store is the one that already worked, pinned here so the
     * convention the others now follow has a test of its own.
     */
    public function test_recording_an_expense_answers_201_and_logs(): void
    {
        $category = $this->expenseCategory();

        $this->postJson('/api/expenses/branch-expenses', [
            'expense_category_id' => $category->id,
            'amount' => 15000,
            'business_branch_id' => $this->branch->id,
            'description' => 'Fuel',
            'payment_date' => now()->toDateString(),
        ])->assertCreated();

        $this->assertSame(1, Expense::count());
        $this->assertTrue(
            ActivityLog::withoutGlobalScopes()->where('log_name', 'Recorded Expense')->exists()
        );
    }

    /**
     * The expense cash flow is written by the controller, not by a model observer, so a
     * seeded or otherwise bypassed expense never reaches the ledger. That is item 18 in
     * the ranking and is deliberately not fixed here; this pins the current, correct
     * behaviour of the endpoint that does work.
     */
    public function test_recording_an_expense_also_records_the_cash_outflow(): void
    {
        $this->assertSame(0, CashFlow::count());

        $this->postJson('/api/expenses/branch-expenses', [
            'expense_category_id' => $this->expenseCategory()->id,
            'amount' => 15000,
            'business_branch_id' => $this->branch->id,
            'payment_date' => now()->toDateString(),
        ])->assertCreated();

        $this->assertSame(1, CashFlow::where('expense_id', Expense::first()->id)->count());
    }
}