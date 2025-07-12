<?php
namespace App\Services;

use App\Models\Offer;
use App\Models\Store;
use App\Models\Product;
use App\Services\Service;
use App\Services\FileStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Jobs\SendNewOfferNotificationJob;
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
        try {
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
            SendNewOfferNotificationJob::dispatch($offer);
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
            if (isset($data['products'])) {
                $offer->products()->sync($data['products']);
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

    /**
     * get offer's form data
     * @param mixed $offerId
     * @return array{code: int, data: array{all: mixed, offer_products: mixed, other_products: \Illuminate\Database\Eloquent\Collection, message: string, status: bool}|array{code: int, data: array{all: \Illuminate\Support\Collection, offer_products: \Illuminate\Support\Collection, other_products: \Illuminate\Database\Eloquent\Collection}, message: string, status: bool}|array{code: int, data: array{products_without_offer: \Illuminate\Database\Eloquent\Collection}, message: string, status: bool}|array{code: int, data: null, message: string, status: bool}}
     */
    public function getFormData(?int $offerId = null){
        $user = Auth::user();

        // إذا كان المستخدم ليس أدمن
        if (!$user->hasRole('admin')) {
            $store = Store::where('manager_id', $user->id)->first();
            if (!$store) {
                return [
                    'status' => false,
                    'message' => 'You do not have a store',
                    'data' => null,
                    'code' => 404
                ];
            }
        }

        $offerProducts = collect();

        if (!empty($offerId)) {
            if ($user->hasRole('admin')) {
                $offer = Offer::with('products')->find($offerId);
            } else {
                $offer = Offer::with('products')
                    ->where('store_id', $store->id)
                    ->find($offerId);
            }

            if (!$offer) {
                return [
                    'status' => false,
                    'message' => 'لم يتم ايجاد العرض ',
                    'data' => null,
                    'code' => 404
                ];
            }

            $offerProducts = $offer->products;
            $storeId = $offer->store_id; 
        } else {
            if ($user->hasRole('admin')) {
                $store = Store::where('manager_id', $user->id)->first();
                if (!$store) {
                    return [
                        'status' => false,
                        'message' => 'يجب أن يكون لديك متجر اولاً',
                        'data' => null,
                        'code' => 404
                    ];
                }
            }
            $storeId = $store->id;
        }

        $productIdsInOffers = DB::table('offer_product')
            ->whereIn('offer_id', function ($query) use ($storeId) {
                $query->select('id')->from('offers')->where('store_id', $storeId);
            })
            ->pluck('product_id')->toArray();

        $productsWithoutOffer = Product::where('store_id', $storeId)
            ->whereNotIn('id', $productIdsInOffers)
            ->get();

        if (!empty($offerId)) {
            return [
                'status' => true,
                'message' => 'Offer-related and available products retrieved successfully',
                'data' => [
                    'mode' => 'edit',
                    'offer_products' => $offerProducts,
                    'other_products' => $productsWithoutOffer,
                    'all' => $offerProducts->merge($productsWithoutOffer)->values()
                ],
                'code' => 200
            ];
        }

        return [
            'status' => true,
            'message' => 'Products without any offer retrieved successfully',
            'data' => [
                'mode' => 'create',
                'products_without_offer' => $productsWithoutOffer
            ],
            'code' => 200
        ];
    }

}
