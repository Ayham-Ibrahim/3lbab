<?php

namespace App\Services;

use App\Models\Complaint;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ComplaintService extends Service
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function create(array $data)
    {
        try {
            return Complaint::create([
                'customer_id' => Auth::id(),
                'manager_id' => $data['manager_id'],
                'content' => $data['content'],
                'image' => isset($data['image']) ? FileStorage::storeFile($data['image'], 'Complaint', 'img') : null,
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
    }
}
