<?php
namespace App\Services;

use App\Models\Product;
use App\Services\FileStorage;
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
            DB::commit();

            return $product->load('images');
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollBack();
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
                'video'        => FileStorage::fileExists($data['video'], $product->image, 'Product', 'vid'),
                'is_available' => $data['is_available'] ?? null,
            ]));

            if (isset($data['images'])) {
                $this->updateProductImages($product, $data['images']);
            }

            DB::commit();
            return $product->load('images');
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollBack();
            $this->throwExceptionJson();
        }
    }

    /**
     * Updates images for the specified product.
     *
     * @param \App\Models\Product $product The product instance.
     * @param array $images The array of image data, each containing a file and optionally an id.
     */
    protected function updateProductImages(Product $product, array $images)
    {
        foreach ($images as $image) {
            if (isset($image['id'])) {
                $this->updateExistingImage($product, $image);
            } else {
                $this->addNewImage($product, $image);
            }
        }
    }

    /**
     * Updates an existing image of the product.
     *
     * @param \App\Models\Product $product The product instance.
     * @param array $image The image data containing id and file.
     */
    protected function updateExistingImage(Product $product, array $image)
    {
        $image = $product->images()->find($image['id']);
        if ($image) {
            $image->update([
                'image' => FileStorage::fileExists($image['file'], $image->image, 'Product', 'img'),
            ]);
        }
    }

    /**
     * Adds a new image to the product.
     *
     * @param \App\Models\Product $product The product instance.
     * @param array $image The image data containing the file.
     */
    protected function addNewImage(Product $product, array $image)
    {
        $product->images()->create([
            'image' => FileStorage::storeFile($image['file'], 'Product', 'img'),
        ]);
    }

}
