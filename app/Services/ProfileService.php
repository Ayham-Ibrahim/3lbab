<?php

namespace App\Services;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileService extends Service
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function update(array $data)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $user->update(array_filter([
                'name' => $data['name'],
                'email' => $data['email'],
            ]));

            $user->info()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'photo' => isset($data['photo']) ? FileStorage::storeFile($data['photo'], 'Profule', 'img') : null,
                    'whatsAppNumber' => $data['whatsAppNumber'] ?? null,
                ]
            );

            DB::commit();

            return $user->load('info');
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollBack();
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
    }

    public function resetPassword(array $data)
    {
        try {
            $user = Auth::user();
            $user->update(['password' => $data['new_password']]);
            return null;
        } catch (\Throwable $th) {
            Log::error($th);
            $this->throwExceptionJson();
        }
    }
}
