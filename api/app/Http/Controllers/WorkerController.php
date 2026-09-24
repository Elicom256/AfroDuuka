<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Models\User;
use App\Models\Worker;
use App\Services\WorkerService;

class WorkerController extends Controller
{
    protected $workerService;
    public function __construct(WorkerService $workerService)
    {
        $this->workerService = $workerService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $business_id = Auth::user()->business_id;
        $workers = Worker::with(["user.role", "user.businessBranch", "attendances"])
                  ->whereHas("user.role", function($q){
                    $q->where("name", "!=", "admin");
                  })
                  ->with("attendances", function($q){
                    $q->latest()
                      ->limit(5);
                  })
                  ->get();
        return response()->json(["message" => "Fetched all Workers", "workers" => $workers]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWorkerRequest $request)
    {
        $validated = $request->validated();
        $worker = $this->workerService->addWorker($validated);
        return response()->json(["message" => "Added Worker", "worker" => $worker]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Worker $worker)
    {
        $worker = $worker->load("user.role", "attendances");
        $att = $this->workerService->workerAttendanceHistory($worker->id);
        return response()->json(["message" => "Fetched Worker", "worker" => $worker, "attendance_history" => $att]);
    }

    /**
     * Update the specified resource in storage.
     */
public function update(UpdateWorkerRequest $request, Worker $worker)
    {
        $validated = $request->validated();
        $worker = $this->workerService->updateWorker($worker, $validated);
        return response()->json(["message" => "Updated Worker", "worker" => $worker]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Worker $worker)
    {
        //
    }
}
