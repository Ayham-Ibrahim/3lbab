<?php

namespace App\Services;

use App\Models\User;
use App\Models\Store;
use App\Services\FcmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreService extends Service
{
    /**
     * Create a new StoreService instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Create a new store with the provided data.
     *
     * @param array $data {
     *     @var int|null    $manager_id      The ID of the store manager
     *     @var string      $name            The name of the store
     *     @var string      $description     The description of the store
     *     @var \Illuminate\Http\UploadedFile $logo The store logo image file
     *     @var \Illuminate\Http\UploadedFile $cover The store cover image file
     *     @var string      $location        The physical location of the store
     *     @var array       $phones          Array of contact phone numbers
     *     @var string      $email           Contact email address
     *     @var string|null $facebook_link   Facebook page URL
     *     @var string|null $instagram_link  Instagram page URL
     *     @var string|null $youtube_link    YouTube channel URL
     *     @var string|null $whatsup_link    WhatsApp contact link
     *     @var string|null $telegram_link   Telegram channel link
     *     @var array|null  $categories      Array of category IDs to associate
     * }
     *
     * @return \App\Models\Store|null
     * @throws \Exception If store creation fails
     */
    public function storeStore(array $data)
    {
        try {
            DB::beginTransaction();

            $store = Store::create([
                'manager_id'     => $data['manager_id'] ?? Auth::id(),
                'name'           => $data['name'],
                'description'    => $data['description'],
                'logo'           => FileStorage::storeFile($data['logo'], 'Stores', 'img'),
                'cover'          => FileStorage::storeFile($data['cover'], 'Stores', 'img'),
                'location'       => $data['location'],
                'phones'         => $data['phones'],
                'email'          => $data['email'],
                'facebook_link'  => $data['facebook_link'] ?? null,
                'instagram_link' => $data['instagram_link'] ?? null,
                'youtube_link'   => $data['youtube_link'] ?? null,
                'whatsup_link'   => $data['whatsup_link'] ?? null,
                'telegram_link'  => $data['telegram_link'] ?? null
            ]);

            if (isset($data['categories'])) {
                $store->categories()->sync($data['categories']);
            }

            DB::commit();

            $manager = User::with('devices')->find($store->manager_id);

            if ($manager && $manager->devices->isNotEmpty()) {
                $fcmService = new FcmService();

                foreach ($manager->devices as $device) {
                    try {
                        $success = $fcmService->sendNotification(
                            $manager,
                            'تم إنشاء متجر جديد',
                            "تم إنشاء المتجر: {$store->name}",
                            $device->fcm_token,
                            [
                                'type' => 'store_created',
                                'store_id' => $store->id
                            ]
                        );

                        if ($success) {
                            Log::info("تم إرسال الإشعار إلى الجهاز {$device->id} للمستخدم {$manager->id}");
                        } else {
                            Log::warning("فشل إرسال الإشعار إلى الجهاز {$device->id} للمستخدم {$manager->id}");
                        }
                    } catch (\Throwable $e) {
                        Log::error("فشل إرسال الإشعار إلى الجهاز {$device->id} للمستخدم {$manager->id}: {$e->getMessage()}");
                    }
                }
            }

            return $store;
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
    }

    /**
     * Update an existing store with the provided data.
     *
     * @param array $data {
     *     @var string|null $name            The updated name of the store
     *     @var string|null $description     The updated description of the store
     *     @var \Illuminate\Http\UploadedFile|null $logo The new logo image file
     *     @var \Illuminate\Http\UploadedFile|null $cover The new cover image file
     *     @var string|null $location        The updated physical location
     *     @var array|null  $phones          Updated array of contact phone numbers
     *     @var string|null $email           Updated contact email address
     *     @var string|null $facebook_link   Updated Facebook page URL
     *     @var string|null $instagram_link  Updated Instagram page URL
     *     @var string|null $youtube_link    Updated YouTube channel URL
     *     @var string|null $whatsup_link    Updated WhatsApp contact link
     *     @var string|null $telegram_link   Updated Telegram channel link
     * }
     * @param \App\Models\Store $store The store model to update
     *
     * @return \App\Models\Store|null
     * @throws \Exception If store update fails
     */
    public function updateStore(array $data, Store $store)
    {
        try {
            DB::beginTransaction();
            $store->update(
                [
                    'name'           => $data['name'] ?? $store->name,
                    'description'    => $data['description'] ?? $store->description,
                    'logo'           => FileStorage::fileExists($data['logo'] ?? null, $store->logo, 'Stores', 'img') ?? str_replace(asset(''), '', $store->logo),
                    'cover'          => FileStorage::fileExists($data['cover'] ?? null, $store->cover, 'Stores', 'img') ?? str_replace(asset(''), '', $store->cover),
                    'location'       => $data['location'] ?? $store->location,
                    'phones'         => !empty($data['phones']) ? $data['phones'] : $store->phones,
                    'email'          => $data['email'] ?? $store->email,
                    'facebook_link'  => $data['facebook_link'] ?? null,
                    'instagram_link' => $data['instagram_link'] ?? null,
                    'youtube_link'   => $data['youtube_link'] ?? null,
                    'whatsup_link'   => $data['whatsup_link'] ?? null,
                    'telegram_link'  => $data['telegram_link'] ?? null
                ]
            );

            if (isset($data['categories'])) {
                $store->categories()->sync($data['categories']);
            }

            DB::commit();

            return $store;
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            if ($th instanceof HttpResponseException) {
                throw $th;
            }
            $this->throwExceptionJson();
        }
    }
}
