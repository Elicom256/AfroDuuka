<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Worker;
use Illuminate\Support\Facades\Auth;

class WorkerService
{
     public function __construct(private ProfileService $profileService) {}


    public function addWorker(array $data)
    {
        return $this->profileService->create($data, function($user, $data){
            $work = Worker::with("user", function($q){
                $q->where("business_id", Auth::user()->business_id);
            })->count();
            return Worker::create([
                    "user_id" => $user->id,
                    "employee_code" => "EMP-" . str_pad($work + 1, 5, "0", STR_PAD_LEFT),
                    "department" => $data["department"] ?? null,
                    "position" => $data["position"] ?? null,
                    "employment_type" => $data["employment_type"] ?? "full_time",
                    "salary" => $data["salary"] ?? null,
                    "hire_date" => $data["hire_date"] ?? null,
                    "status" => "active",
            ]);
        });
    }

    // update worker 
    public function updateWorker(Worker $worker, array $data){
        return $this->profileService->updateProfile($worker->user, $data, function($user, $data) use($worker){
            $worker->update([
                "user_id" => $user->id,
                "department" => $data["department"] ?? $worker->department,
                "position" => $data["position"] ?? $worker->position,
                "employment_type" => $data["employment_type"] ?? $worker->employment_type,
                "salary" => $data["salary"] ?? $worker->salary,
                "hire_date" => $data["hire_date"] ?? $worker->hire_date,
                "status" => "active" ?? $worker->active,
            ]);
            return $worker;
        } );
    }

    // attendances
    public function workerAttendanceHistory(string $workerId){
        $attendance = Attendance::where("worker_id", $workerId);
        $present = (clone $attendance)->where("status", "present")->count();
        $absent = (clone $attendance)->where("status", "absent")->count();
        return [
            "present" => $present,
            "absent" => $absent
        ];
    }
}