<?php

namespace App\Http\Controllers;

use App\Http\Requests\Size\StoreSizeRequest;
use App\Http\Requests\Size\UpdateSizeRequest;
use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    /**
     * Create a new ColorController instance.
     *
     */
    public function __construct()
    {
        $this->middleware(['permission:list-sizes'])->only('index');
        $this->middleware(['permission:show-sizes'])->only('show');
        $this->middleware(['permission:store-sizes'])->only('store');
        $this->middleware(['permission:update-sizes'])->only('update');
        $this->middleware(['permission:delete-sizes'])->only('destroy');
        $this->middleware(['permission:toggle-available-sizes'])->only('toggleAvailable');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $this->success(
            Size::select('id', 'type', 'size_code', 'is_available')
                ->available($request->input('is_available'))
                ->get(),
            'Sizes retrieved successfully'
        );
    }

    /**
     * Retrieve all available sizes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailable()
    {
        return $this->success(
            Size::select('id', 'type', 'size_code')
                ->available(true)
                ->get(),
            'Available Sizes retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSizeRequest $request)
    {
        return $this->success(
            Size::create($request->validated()),
            'Size created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Size $size)
    {
        return $this->success($size, 'Size retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSizeRequest $request, Size $size)
    {
        $size->update(array_filter($request->validated()));
        return $this->success($size, 'Size updated successfully');
    }

    /**
     * Toggle the availability status of the specified resource.
     *
     * @param  \App\Models\Size  $size
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAvailable(Size $size)
    {
        $size->update(['is_available' => !$size->is_available]);
        return $this->success($size, 'The Size has been successfully Toggled');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Size $size)
    {
        $size->delete();
        return $this->success(null, 'Size deleted successfully', 204);
    }
}
