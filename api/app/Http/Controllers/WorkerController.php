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
        $workers = Worker::with(['user.role', 'user.businessBranch', 'attendances'])
            ->whereHas('user.role', function ($q) {
                $q->where('name', '!=', 'Executive');
            })
            ->with('attendances', function ($q) {
                $q->latest()
                    ->limit(5);
            })
            ->get();

        return response()->json(['message' => 'Fetched all Workers', 'workers' => $workers]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWorkerRequest $request)
    {
        $validated = $request->validated();
        $worker = $this->workerService->addWorker($validated);

        return response()->json(['message' => 'Added Worker', 'worker' => $worker]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Worker $worker)
    {
        $worker = $worker->load('user.role', 'attendances');
        $att = $this->workerService->workerAttendanceHistory($worker->id);

        return response()->json(['message' => 'Fetched Worker', 'worker' => $worker, 'attendance_history' => $att]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkerRequest $request, Worker $worker)
    {
        $validated = $request->validated();
        $worker = $this->workerService->updateWorker($worker, $validated);

        return response()->json(['message' => 'Updated Worker', 'worker' => $worker]);
    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * Remove the specified resource from storage.
     *
     * Refuses rather than silently succeeding. This was an empty body behind
     * `Route::apiResource('workers')`, which still includes destroy — so the endpoint
     * answered 200 to a caller that had just been told a worker was removed. See
     * checked.md P1-24.
     *
     * Removing a worker is done on the user record, by UserController::destroy(), which
     * checks the tenant, the permission and the "not yourself" case and enforces the
     * restriction on restricted roles. Deliberately having two delete paths would let
     * one of them be the unguarded one, so this answers 405 and points at the real one
     * instead of growing a second implementation.
     */
    public function destroy(Worker $worker)
    {
        abort(405, 'Delete the worker through their user account: DELETE /api/users/workers/{user}.');
    }
}
