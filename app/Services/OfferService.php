<?php
namespace App\Services;

use App\Models\Offer;
use App\Models\Product;
use App\Services\Service;
use App\Services\FileStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class OfferService extends Service
{
    /**
     * add new offer
     * @param array $data
     * @return Offer|null
     */
    public function storeOffer(array $data)
    {
    
        $productIds = $data['products'] ?? [];

        if (!empty($productIds)) {
            $count = Product::whereIn('id', $productIds)
                ->where('store_id', $data['store_id'])
                ->count();

            if ($count !== count($productIds)) {
                throw ValidationException::withMessages([
                    'products' => ['بعض المنتجات لا تنتمي للمتجر المحدد.'],
                ]);
            }
        }
        // try {
            DB::beginTransaction();
            $offer =  Offer::create([
                'image'               => FileStorage::storeFile($data['image'], 'Offer', 'img'),
                'description'         => $data['description'],
                'store_id'            => $data['store_id'],
                'discount_percentage' => $data['discount_percentage'],
                'starts_at'           => $data['starts_at'],
                'ends_at'             => $data['ends_at'],
            ]);

            if (!empty($productIds)) {
                $offer->products()->sync($productIds);
            }

            DB::commit();
            return $offer;
        // } catch (\Throwable $th) {
        //     Log::error($th);
        //     DB::rollBack();
        //     if ($th instanceof HttpResponseException) {
        //         throw $th;
        //     }
        //     $this->throwExceptionJson();
        // }
    }

    /**
     * update exists offer
     * @param \App\Models\Offer $offer
     * @param array $data
     * @return Offer|null
     */
    public function updateOffer(Offer $offer,array $data)
    {
        try {
            DB::beginTransaction();
            $offer->update(array_filter([
                'image'               => FileStorage::fileExists($data['image'] ?? null, $offer->image, 'Category', 'img'),
                'description'         => $data['description'] ?? $offer->description,
                'store_id'            => $data['store_id'] ?? $offer->store_id,
                'discount_percentage' => $data['discount_percentage'] ?? $offer->discount_percentage,
                'starts_at'           => $data['starts_at'] ?? $offer->starts_at,
                'ends_at'             => $data['ends_at'] ?? $offer->ends_at,
            ]));
            if (isset($data['product_ids'])) {
                $offer->products()->sync($data['product_ids']);
            }
            DB::commit();
            return $offer;
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
