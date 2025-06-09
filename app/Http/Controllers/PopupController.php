<?php

namespace App\Http\Controllers;

use App\Http\Requests\Popup\PopupRequest;
use App\Models\Popup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PopupController extends Controller
{
    /**
     * Handle popup operations
     *
     * @param PopupRequest $request
     * @return JsonResponse
     */
    public function handle(PopupRequest $request)
    {
        // Modification operations - Admin only
        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return $this->error('Unauthorized', 403);
        }

        try {
            $popup = Popup::firstOrNew();
            $popup->fill($request->validated());
            $popup->save();

            return $this->success($popup, 'Popup saved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update popup', 500);
        }
    }

    public function getInfo()
    {
        $popup = Popup::first();
        return $this->success($popup);
    }

}
