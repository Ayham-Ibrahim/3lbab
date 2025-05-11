<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserContoller extends Controller
{
    /**
     * The service class responsible for handling user-related business logic.
     *
     * @var \App\Services\UserService
     */
    protected $userService;

    /**
     * Create a new UserController instance and inject the UserService.
     *
     * @param \App\Services\UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware(['permission:list-users'])->only('index');
        $this->middleware(['permission:show-users'])->only('show');
        $this->middleware(['permission:store-users'])->only('store');
        $this->middleware(['permission:update-users'])->only('update');
        $this->middleware(['permission:delete-users'])->only('destroy');
        $this->middleware(['permission:toggle-available-users'])->only('toggleAvailable');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $is_available = ($request->input('is_available') === null)
            ? null
            : ($request->input('is_available') == 'true' ? 1 : 0);

        return $this->paginate(
            User::with('info:id,user_id,photo')
                ->select('id', 'name', 'is_available')
                ->available($is_available)
                ->paginate(),
            'Users retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        return $this->success(
            $this->userService->storeUser($request->validated()),
            'User created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return $this->success(
            $user->load('info'),
            'User retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        return $this->success(
            $this->userService->updateUser($request->validated(), $user),
            'User updated successfully'
        );
    }

    /**
     * Toggle the availability status of the specified resource.
     *
     * @param  \App\Models\User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAvailable(User $user)
    {
        $user->update(['is_available' => !$user->is_available]);
        return $this->success($user, 'The User has been successfully Toggled');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return $this->success(
            null,
            'User deleted successfully',
            200
        );
    }
}
