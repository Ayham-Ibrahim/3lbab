<?php

namespace App\Services;

use App\Models\Product;
use App\Services\FileStorage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductService extends Service
{

    /**
     * add new product to the database
     * @param mixed $data
     * @return Product|null
     * @throws \Exception If an error occurs during the transaction.
     */
    public function storeProduct($data)
    {
        try {
            DB::beginTransaction();
            $product = Product::create([
                'store_id'     => $data['store_id'],
                'category_id'  => $data['category_id'],
                'name'         => $data['name'],
                'description'  => $data['description'],
                'price'        => $data['price'],
                'video'        => isset($data['video']) ? FileStorage::storeFile($data['video'], 'Product', 'vid') : null,
                'is_available' => $data['is_available'],
            ]);

            $this->storeProductImages($product, $data['images']);

            $this->storeProductVariants($product, $data['variants']);
            DB::commit();

            return $product->load('images');
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
     * Updates an existing product along with its images.
     * @param mixed $data
     * @param \App\Models\Product $product
     * @return Product|null
     * @throws \Exception If an error occurs during the transaction.
     */
    public function updateProduct($data, Product $product)
    {
        try {
            DB::beginTransaction();

            $product->update([
                'store_id'     => $data['store_id'] ?? $product->store_id,
                'category_id'  => $data['category_id'] ?? $product->category_id,
                'name'         => $data['name'] ?? $product->name,
                'description'  => $data['description'] ?? $product->description,
                'price'        => $data['price'] ?? $product->price,
                'video'        => FileStorage::fileExists($data['video'] ?? null, $product->video, 'Product', 'vid'),
                'is_available' => $data['is_available'] ?? $product->is_available,
            ]);

            if (isset($data['images'])) {
                $product->images()->delete();
                $this->storeProductImages($product, $data['images']);
            }

            if (isset($data['variants'])) {
                $this->updateProductVariants($product, $data['variants']);
            }

            DB::commit();
            return $product->load('images');
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
     * Stores images for the specified product.
     *
     * @param \App\Models\Product $product The product instance.
     * @param array $images The array of image data, each containing a file.
     */
    protected function storeProductImages(Product $product, array $images)
    {
        $productImages = collect($images)->map(function ($file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                return [
                    'image' => FileStorage::storeFile($file, 'Product', 'img'),
                ];
            }

            Log::error('Invalid image file type:', ['image' => $file]);
            return null;
        })->filter()->toArray();

        if (!empty($productImages)) {
            $product->images()->createMany($productImages);
        }
    }

    /**
     * Store new product variants
     * @param Product $product
     * @param array $variants
     */
    protected function storeProductVariants(Product $product, array $variants)
    {
        $product->variants()->createMany($variants);
    }

    protected function updateProductVariants(Product $product, array $variants)
    {
        // $existingVariants = $product->variants()->get()->keyBy('id');
        // $sentIds = collect($variants)->pluck('id')->filter()->all();

        // foreach ($variants as $variantData) {
        //     if (isset($variantData['id']) && $existingVariants->has($variantData['id'])) {
        //         $variant = $existingVariants[$variantData['id']];

        //         $isUsedInOrders = $variant->orderItems()->exists();
        //         $isUsedInCarts  = $variant->cartItems()->exists();

        //         if ($isUsedInOrders || $isUsedInCarts) {
        //             $variant->update(['is_active' => false]);
        //             $product->variants()->create($variantData);
        //         } else {
        //             $variant->update($variantData);
        //         }
        //     } else {
        //         $product->variants()->create($variantData);
        //     }
        // }
         $existingVariants = $product->variants()->get()->keyBy('id');

    foreach ($variants as $variantData) {
        $variantId = $variantData['id'] ?? null;

        \Log::info('variantId is here = ' . $variantId);

        if ($variantId && $existingVariants->has($variantId)) {
            $variant = $existingVariants[$variantId];

            $isUsedInOrders = $variant->orderItems()->exists();
            $isUsedInCarts  = $variant->cartItems()->exists();

            $isSameKey = 
                $variant->product_id == $product->id &&
                $variant->color_id == ($variantData['color_id'] ?? $variant->color_id) &&
                $variant->size_id == ($variantData['size_id'] ?? $variant->size_id);

            if ($isSameKey) {
                // تحديث الكمية أو أي بيانات أخرى مباشرة بدون فحص تكرار
                $variant->update($variantData);
                continue;  // ننتقل للعنصر التالي من المتغيرات
            }


            if (($isUsedInOrders || $isUsedInCarts) && !$isSameKey) {
                // تحقق إذا كان هناك تعارض في القيم الثلاثية مع سجل آخر
                $duplicate = ProductVariant::where('product_id', $product->id)
                    ->where('color_id', $variantData['color_id'])
                    ->where('size_id', $variantData['size_id'])
                    ->where('id', '!=', $variant->id)
                    ->first();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'variants' => ['هناك متغير آخر بنفس اللون والمقاس موجود بالفعل.']
                    ]);
                }

                // سجل مرتبط بطلب أو سلة وتغييره سيسبب خطأ -> نقوم بتعطيله وإنشاء جديد
                $variant->update(['is_active' => false]);
                $product->variants()->create($variantData);
            } else {
                // نفس المفتاح أو فقط تحديث للكمية
                $variant->update($variantData);
            }
        } else {
            // سجل جديد بدون ID، تحقق أولًا من عدم وجود تطابق
            $duplicate = ProductVariant::where('product_id', $product->id)
                ->where('color_id', $variantData['color_id'])
                ->where('size_id', $variantData['size_id'])
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'variants' => ['هناك متغير بنفس اللون والمقاس موجود مسبقًا.']
                ]);
            }

            $product->variants()->create($variantData);
        }
    }
    }
}
