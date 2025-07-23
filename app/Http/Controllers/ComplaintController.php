<?php

namespace App\Http\Controllers;

use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComplaintController extends Controller
{
    /**
     * The service class responsible for handling Complaint-related business logic.
     *
     * @var \App\Services\ComplaintService
     */
    protected $complaintService;

    /**
     * Create a new ComplaintController instance and inject the ComplaintService.
     *
     * @param \App\Services\ComplaintService $complaintService
     */
    public function __construct(ComplaintService $complaintService)
    {
        $this->complaintService = $complaintService;
        // $this->middleware(['permission:list-complaints'])->only('index');
        // $this->middleware(['permission:list-my-complaints'])->only('managerComplaints');
        // $this->middleware(['permission:is-readed-complaints'])->only('markAsRead');
        // $this->middleware(['permission:delete-complaints'])->only('destroy');
    }

    /**
     * Store a newly created complaint in storage.
     */
    public function store(StoreComplaintRequest $request)
    {
        return $this->success(
            $this->complaintService->create($request->validated()),
            'Complaint created successfully',
            201
        );
    }

    /**
     * Get list of store managers who have stores
     */
    public function getAdmins()
    {
        $users = User::role(['admin', 'storeManager'])
            ->with(['store' => function ($query) {
                $query->select('id', 'manager_id', 'name as store_name');
            }])
            ->where(function ($query) {
                $query->whereHas('roles', fn($q) => $q->where('name', 'admin'))
                    ->orWhereHas('store');
            })
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->hasRole('storeManager') && $user->store
                        ? $user->store->store_name
                        : $user->name,
                ];
            });

        return $this->success($users);
    }

    /**
     * Display a listing of all complaints (for Admin).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $isReadedFilter = $request->has('is_readed')
            ? filter_var($request->input('is_readed'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $myComplaint = $request->has('myComplaint')
            ? filter_var($request->input('myComplaint'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $user = Auth::user();
        if ($user && $user->hasRole('admin')) {
            $complaints = Complaint::with([
                'customer:id,name',
                'manager:id,name'
            ])
                ->myComplaint(false)
                ->readStatus($isReadedFilter)
                ->latest()
                ->get();
        }

        $complaints = Complaint::with([
            'customer:id,name',
            'manager:id,name'
        ])
            ->myComplaint(true)
            ->readStatus($isReadedFilter)
            ->latest()
            ->get();

        return $this->success($complaints, 'All complaints retrieved successfully.');
    }

    /**
     * Display a listing of complaints for the currently authenticated manager.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function managerComplaints(Request $request): JsonResponse
    {
        $isReadedFilter = $request->has('is_readed')
            ? filter_var($request->input('is_readed'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        $complaints = Complaint::with('customer:id,name')
            ->myComplaint(true)
            ->readStatus($isReadedFilter)
            ->latest()
            ->get();

        return $this->success($complaints, 'Your complaints retrieved successfully.');
    }

    /**
     * Mark a specific complaint as read by the authenticated manager.
     *
     * @param Complaint $complaint The complaint instance (Route Model Binding)
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id): JsonResponse
    {
        $complaint = Complaint::where('id',$id)->first();
        $managerId = Auth::id();
        if ($complaint->manager_id != $managerId) {
            return $this->error('Unauthorized. This complaint is not assigned to you.', 403);
        }

        if ($complaint->is_readed) {
            return $this->success($complaint, 'Complaint was already marked as read.');
        }

        $complaint->is_readed = true;
        $complaint->save();

        return $this->success($complaint, 'Complaint marked as read successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Complaint $complaint)
    {
        if ($complaint->manager_id !== Auth::id()) {
            return $this->error('Unauthorized. This complaint is not assigned to you.', 403);
        }
        $complaint->delete();
        return $this->success(null, 'Complaint deleted successfully', 200);
    }
}
