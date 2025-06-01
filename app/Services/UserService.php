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

            if (!empty($data['photo']) || !empty($data['location']) || !empty($data['whatsAppNumber'])) {
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
                    'name' => $data['name'] ?? $user->name,
                    'email' => $data['email'] ?? $user->email,
                    'password' => $data['password'] ?? $user->password,
                ])
            );

            if (!empty($data['role'])) {
                $user->syncRoles([$data['role']]);
            }

            if (!empty($data['photo']) || !empty($data['location']) || !empty($data['whatsAppNumber'])) {
                $user->info()->update(array_filter([
                    'photo' => isset($data['photo']) ? FileStorage::storeFile($data['photo'], 'Profule', 'img') : $user->info->photo,
                    'whatsAppNumber' => $data['whatsAppNumber']  ?? $user->info->whatsAppNumber,
                    'location' => $data['location'] ?? $user->info->location,
                ]));
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
