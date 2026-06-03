<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\Worker;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = Business::where("email", "testbusinessone@gmail.com")->value("id");

        $branchId = BusinessBranch::where("business_id", $businessId)
            ->where("name", "Main Branch")
            ->value("id");

        $workers = Worker::with(["user"])
            ->whereHas("user", function ($q) use ($businessId, $branchId) {
                $q->where("business_id", $businessId)
                ->where("business_branch_id", $branchId);
            })
            ->get();

        $statuses = ['present', 'late', 'excused', 'absent'];

        foreach ($workers as $worker) {

            // random status per worker
            $status = $statuses[array_rand($statuses)];

            // base date (today)
            $date = Carbon::now()->format('Y-m-d');

            // default times
            $checkIn = Carbon::parse("$date 08:00:00");
            $checkOut = Carbon::parse("$date 17:00:00");

            // tweak based on status
            if ($status === 'late') {
                $checkIn->addMinutes(rand(10, 60));
            }

            if ($status === 'excused') {
                $checkIn = Carbon::parse("$date 09:00:00");
                $checkOut = Carbon::parse("$date 14:00:00");
            }

            if ($status === 'absent') {
                $checkIn = null;
                $checkOut = null;
            }

            Attendance::updateOrCreate(
                ['worker_id' => $worker->id, 'session' => 'morning', 'check_in' => $checkIn, 'check_out' => $checkOut],
                [
                'business_branch_id' => $branchId,
                'status' => $status,
                'remarks' => match ($status) {
                    'present' => 'Arrived on time',
                    'late' => 'Traffic delay',
                    'excused' => 'Approved leave',
                    'absent' => 'Did not report to work',
                },
            ]);
        }
            $num = $workers?->count();
            $this->command->info("✅ Seeded $num Employee Attendances!");
        
    }
}