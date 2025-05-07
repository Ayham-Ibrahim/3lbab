<?php

namespace App\Http\Controllers;

use App\Http\Requests\Complaint\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
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
                $query->whereHas('store')
                    ->orWhereHas('roles', fn($q) => $q->where('name', 'admin'));
            })
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                if ($user->store) {
                    $data['store_id'] = $user->store->id;
                    $data['store_name'] = $user->store->store_name;
                }

                return $data;
            });

        return $this->success($users);
    }
}
