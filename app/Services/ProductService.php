<?php

namespace App\Services;

use App\Models\Product;
use App\Services\FileStorage;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                $product->variants()->delete();
                $this->storeProductVariants($product, $data['variants']);
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
        $productImages = collect($images)->map(function ($imageData) {
            if (isset($imageData['file'])) {
                $file = $imageData['file'];
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    return [
                        'image' => FileStorage::storeFile($file, 'Product', 'img'),
                    ];
                }
            }
            Log::error('Invalid image file type:', ['image' => $imageData]);
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
}
