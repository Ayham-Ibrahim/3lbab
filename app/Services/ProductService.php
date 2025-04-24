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
                'video'        => FileStorage::storeFile($data['video'], 'Product', 'vid'),
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

            $product->update(array_filter([
                'store_id'     => $data['store_id'] ?? null,
                'category_id'  => $data['category_id'] ?? null,
                'name'         => $data['name'] ?? null,
                'description'  => $data['description'] ?? null,
                'price'        => $data['price'] ?? null,
                'video'        => FileStorage::fileExists($data['video'] ?? null, $product->image, 'Product', 'vid'),
                'is_available' => $data['is_available'] ?? null,
            ]));

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
        $productImages = collect($images)->map(function ($photo) {
            return [
                'image' => FileStorage::storeFile($photo['file'], 'Product', 'img'),
            ];
        })->toArray();

        $product->images()->createMany($productImages);
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
