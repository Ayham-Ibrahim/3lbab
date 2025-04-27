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
            // إذا كانت variants هي JSON مشوّه، قم بفك تشفيرها هنا
            if (is_string($data['variants'])) {
                $decodedVariants = json_decode($data['variants'], true);
    
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // في حال كان هناك خطأ في فك التشفير، يمكنك التعامل مع الخطأ هنا
                    Log::error('Invalid JSON for variants', ['error' => json_last_error_msg()]);
                    throw new \Exception('Invalid variants JSON.');
                }
    
                $data['variants'] = $decodedVariants;
            }
    
            // التحقق من وجود variant بشكل صحيح
            $variants = $data['variants'] ?? [];
            $hasColor = isset($variants[0]['color_id']);
            $hasSize = isset($variants[0]['size_id']);
    
            foreach ($variants as $index => $variant) {
                // التحقق من أن جميع المتغيرات تتبع نفس النمط (color_id و size_id)
                if ((isset($variant['color_id']) !== $hasColor) || (isset($variant['size_id']) !== $hasSize)) {
                    throw new \Exception('جميع المتغيرات يجب أن تتبع نفس النمط (لون و/أو مقاس)');
                }
            }
    
            DB::beginTransaction();
    
            // إنشاء المنتج
            $product = Product::create([
                'store_id'     => $data['store_id'],
                'category_id'  => $data['category_id'],
                'name'         => $data['name'],
                'description'  => $data['description'],
                'price'        => $data['price'],
                'video'        => isset($data['video']) ? FileStorage::storeFile($data['video'], 'Product', 'vid') : null,
                'is_available' => $data['is_available'],
            ]);
    
            // تخزين الصور
            $this->storeProductImages($product, $data['images']);
    
            // تخزين المتغيرات
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
