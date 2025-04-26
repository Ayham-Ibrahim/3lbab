<?php

namespace App\Http\Controllers;

use App\Http\Requests\Color\StoreColorRequest;
use App\Http\Requests\Color\UpdateColorRequest;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{

    /**
     * Create a new ColorController instance.
     *
     */
    public function __construct()
    {
        $this->middleware(['permission:list-colors'])->only('index');
        $this->middleware(['permission:show-colors'])->only('show');
        $this->middleware(['permission:store-colors'])->only('store');
        $this->middleware(['permission:update-colors'])->only('update');
        $this->middleware(['permission:delete-colors'])->only('destroy');
        $this->middleware(['permission:toggle-available-colors'])->only('toggleAvailable');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $is_available = ($request->input('is_available') === null)
            ? null
            : ($request->input('is_available') == 'true' ? 1 : 0);

        return $this->success(
            Color::select('id', 'name', 'hex_code', 'is_available')
                ->available($is_available)
                ->get(),
            'Colors retrieved successfully'
        );
    }

    /**
     * Retrieve all available colors.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailable()
    {
        return $this->success(
            Color::select('id', 'name', 'hex_code')->available(true)->get(),
            'Available Colors retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreColorRequest $request)
    {
        return $this->success(
            Color::create($request->validated()),
            'Color created successfully',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Color $color)
    {
        return $this->success($color, 'Color retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateColorRequest $request, Color $color)
    {
        $color->update(array_filter($request->validated()));
        return $this->success($color, 'Color updated successfully');
    }


    /**
     * Toggle the availability status of the specified resource.
     *
     * @param  \App\Models\Color  $color
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAvailable(Color $color)
    {
        $color->update(['is_available' => !$color->is_available]);
        return $this->success($color, 'The Color has been successfully Toggled');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Color $color)
    {
        $color->delete();
        return $this->success(null, 'Color deleted successfully', 204);
    }
}
