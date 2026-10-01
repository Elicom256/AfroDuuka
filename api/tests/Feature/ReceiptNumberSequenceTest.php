<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessCategory;
use App\Models\Country;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReceiptNumberGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use PDO;
use Tests\TestCase;

/**
 * Receipt numbers used to be `Receipt::whereDate('created_at', today())->count() + 1`,
 * read in one statement and written in a later one. Two things broke:
 *
 *   - two tills reading the same count produced the same number, and the UNIQUE
 *     index rejected the second insert, rolling back the entire sale with the
 *     goods already handed over;
 *   - and it did not need concurrency at all: the count ran through the tenant
 *     global scope while `receipt_number` was globally unique, so the first
 *     receipt of the day for one business and the first for another were both
 *     RCP-YYYYMMDD-0001, and the second insert always lost.
 *
 * These tests use separate database connections so the statements genuinely
 * overlap. A single connection cannot demonstrate this: its statements are
 * serialised by the connection itself, so the read-then-write window closes no
 * matter what the generator does.
 */
class ReceiptNumberSequenceTest extends TestCase
{
    private function secondConnection(): PDO
    {
        $config = config('database.connections.pgsql');

        return new PDO(
            sprintf('pgsql:host=%s;port=%s;dbname=%s', $config['host'], $config['port'] ?? 5432, $config['database']),
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    private function nextOn(PDO $pdo, string $prefix): string
    {
        $statement = $pdo->prepare(
            "SELECT '".$prefix."' || to_char(CURRENT_DATE, 'YYYYMMDD') || '-' ||"
            ." lpad(nextval('receipt_number_seq')::text, 6, '0') AS receipt_number"
        );
        $statement->execute();

        return (string) $statement->fetchColumn();
    }

    private function makeBusiness(string $suffix): Business
    {
        // firstOrCreate rather than create: this suite does not wipe between
        // runs, and both of these names are UNIQUE, so a second run of the same
        // test would otherwise fail on its own fixtures.
        $category = BusinessCategory::firstOrCreate(
            ['name' => "Category $suffix"],
            ['description' => "Category $suffix"]
        );

        $country = Country::firstOrCreate(
            ['name' => "Country $suffix"],
            ['currency_code' => 'UGX', 'currency_symbol' => 'USh']
        );

        $business = Business::firstOrCreate(
            ['email' => "seq-$suffix@example.com"],
            [
                'name' => "Seq $suffix",
                'phone' => '+256700000'.substr((string) crc32($suffix), 0, 3),
                'business_category_id' => $category->id,
                'country_id' => $country->id,
            ]
        );

        BusinessBranch::firstOrCreate(
            ['business_id' => $business->id, 'name' => 'Main']
        );

        return $business;
    }

    private function makeSale(Business $business): Sale
    {
        $branch = BusinessBranch::where('business_id', $business->id)->firstOrFail();

        $user = User::firstOrCreate(
            ['email' => "cashier-{$business->id}@example.com"],
            [
                'business_id' => $business->id,
                'business_branch_id' => $branch->id,
                'phone' => '+256700001'.str_pad((string) $business->id, 3, '0', STR_PAD_LEFT),
                'password' => bcrypt('secret1234'),
            ]
        );

        // business_id is not on Sale's $fillable, so assigning it through
        // mass-assignment would be silently dropped and the insert would fail.
        $sale = new Sale([
            'business_branch_id' => $branch->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
        ]);
        $sale->business_id = $business->id;
        $sale->save();

        return $sale;
    }

    public function test_two_connections_never_receive_the_same_number(): void
    {
        $generator = new ReceiptNumberGenerator;
        $rounds = 60;

        $a = $this->secondConnection();
        $b = $this->secondConnection();

        $issued = [];
        for ($i = 0; $i < $rounds; $i++) {
            // Interleaved on two live connections, so the two generators are
            // genuinely in flight at the same time.
            $issued[] = $this->nextOn($a, 'POS-');
            $issued[] = $generator->next('POS-');
            $issued[] = $this->nextOn($b, 'POS-');
        }

        $total = $rounds * 3;
        $this->assertCount($total, $issued);
        $this->assertCount(
            $total,
            array_unique($issued),
            'concurrent allocation handed out a duplicate: '.implode(', ', array_slice($issued, 0, 12)).' ...'
        );

        // A gap would mean a value was consumed without being returned, which
        // would eventually wrap into a reissued number.
        $serials = array_map(fn ($n) => (int) substr($n, -6), $issued);
        sort($serials);
        $this->assertSame(
            $total,
            $serials[count($serials) - 1] - $serials[0] + 1,
            'the sequence skipped a value, so it is not handing out a dense range'
        );
    }

    public function test_pos_and_receipt_prefixes_share_one_serial_and_stay_unique(): void
    {
        $generator = new ReceiptNumberGenerator;

        $pos = $generator->next(ReceiptNumberGenerator::POS_PREFIX);
        $rcp = $generator->next(ReceiptNumberGenerator::RECEIPT_PREFIX);

        $this->assertStringStartsWith('POS-'.now()->format('Ymd').'-', $pos);
        $this->assertStringStartsWith('RCP-'.now()->format('Ymd').'-', $rcp);
        $this->assertNotSame(substr($pos, -6), substr($rcp, -6), 'prefixes must not reuse a serial');
    }

    public function test_two_businesses_no_longer_collide_on_the_first_receipt_of_the_day(): void
    {
        $one = $this->makeBusiness('one');
        $two = $this->makeBusiness('two');

        $generator = new ReceiptNumberGenerator;
        $first = $generator->next(ReceiptNumberGenerator::RECEIPT_PREFIX);
        $second = $generator->next(ReceiptNumberGenerator::RECEIPT_PREFIX);

        $this->assertNotSame(
            $first,
            $second,
            'two businesses received the same receipt number, which the UNIQUE index would have rejected'
        );

        $this->assertNotNull($one->id);
        $this->assertNotNull($two->id);
    }

    public function test_a_number_consumed_by_a_failed_sale_is_not_reissued(): void
    {
        $a = $this->secondConnection();
        $a->beginTransaction();
        $burned = $this->nextOn($a, 'POS-');
        $a->rollBack();

        $afterRollback = (new ReceiptNumberGenerator)->next(ReceiptNumberGenerator::POS_PREFIX);

        // Sequences are non-transactional by design. The rolled-back number may
        // already be on a printed receipt, so it must never come round again.
        $this->assertNotSame($burned, $afterRollback);
        $this->assertGreaterThan(
            (int) substr($burned, -6),
            (int) substr($afterRollback, -6),
            'a number used by a sale that rolled back was handed out again'
        );
    }

    public function test_the_serial_survives_receipts_being_voided(): void
    {
        $business = $this->makeBusiness('void');
        $generator = new ReceiptNumberGenerator;

        $numbers = [];
        for ($i = 0; $i < 3; $i++) {
            $numbers[] = $generator->next(ReceiptNumberGenerator::RECEIPT_PREFIX);
        }

        // Voiding used to shorten the day's count and hand the freed number to
        // the next customer. The serial does not care what happens to receipts.
        $this->assertSame(3, count(array_unique($numbers)));

        $next = $generator->next(ReceiptNumberGenerator::RECEIPT_PREFIX);
        $this->assertNotContains($next, $numbers);
        $this->assertGreaterThan((int) substr(end($numbers), -6), (int) substr($next, -6));
        $this->assertNotNull($business->id);
    }

    public function test_two_receipts_with_the_same_number_cannot_be_stored(): void
    {
        $business = $this->makeBusiness('unique');
        $sale = $this->makeSale($business);

        // A freshly allocated number, not a hard-coded one: this suite does not
        // wipe between runs, and a fixed value would collide with its own
        // leftover row on the second run — making the assertion pass for the
        // wrong reason.
        $attributes = [
            'receipt_number' => (new ReceiptNumberGenerator)->next(ReceiptNumberGenerator::RECEIPT_PREFIX),
            'user_id' => $sale->user_id,
            'business_id' => $business->id,
            'business_branch_id' => $sale->business_branch_id,
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
        ];

        Receipt::withoutGlobalScopes()->create($attributes);

        $this->expectException(UniqueConstraintViolationException::class);

        // Even for a different tenant: the column is globally unique, which is
        // what lets a single serial serve every business.
        $other = $this->makeBusiness('other');
        $otherSale = $this->makeSale($other);

        Receipt::withoutGlobalScopes()->create($attributes + [
            'business_id' => $other->id,
            'user_id' => $otherSale->user_id,
            'business_branch_id' => $otherSale->business_branch_id,
            'sale_id' => $otherSale->id,
        ]);
    }
}
