<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserService extends Service
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function storeUser(array $data)
    {
        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->assignRole($data['role']);

            if ($data['photo'] || $data['location'] || $data['whatsAppNumber']) {
                $user->info()->create([
                    'photo' => isset($data['photo']) ? FileStorage::storeFile($data['photo'], 'Profule', 'img') : null,
                    'whatsAppNumber' => $data['whatsAppNumber'] ?? null,
                    'location' => $data['location'] ?? null,
                ]);
            }
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

    public function updateUser(array $data, User $user)
    {
        try {
            DB::beginTransaction();
            $user->update(
                array_filter([
                    'name' => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'password' => $data['password'] ?? null,
                ])
            );

            if ($data['role']) {
                $user->assignRole($data['role']);
            }

            if ($data['photo'] || $data['location'] || $data['whatsAppNumber']) {
                $user->info()->update([
                    'photo' => isset($data['photo']) ? FileStorage::storeFile($data['photo'], 'Profule', 'img') : null,
                    'whatsAppNumber' => $data['whatsAppNumber'] ?? null,
                    'location' => $data['location'] ?? null,
                ]);
            }
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
}
