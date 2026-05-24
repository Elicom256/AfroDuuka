<?php

namespace App\Services;

use App\Models\Worker;

class WorkerService
{
     public function __construct(private ProfileService $profileService) {}

    public function addWorker(array $data)
    {
        
        return $this->profileService->create($data, function($user, $data){
            return Worker::create([
                    "user_id" => $user->id,
                    "employee_code" => "EMP-" . str_pad(Worker::count() + 1, 5, "0", STR_PAD_LEFT),
                    "department" => $data["department"] ?? null,
                    "position" => $data["position"] ?? null,
                    "employment_type" => $data["employment_type"] ?? "full_time",
                    "salary" => $data["salary"] ?? null,
                    "hire_date" => $data["hire_date"] ?? null,
                    "status" => "active",
            ]);
        });
    }
}