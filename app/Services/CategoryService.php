<?php

namespace App\Services;

use App\Models\Category;
use App\Services\FileStorage;
use Illuminate\Support\Facades\Log;

class CategoryService extends Service {

    /**
     * store new category in database
     * @param mixed $data
     * @return Category|null
     */
    public function storeCategory($data)
    {
        try {
            return Category::create([
                'name'           => $data['name'],
                'image'          => FileStorage::storeFile($data['image'], 'Category', 'img'),
            ]);
        } catch (\Throwable $th) {
            Log::error($th);
            $this->throwExceptionJson();
        }
    }

    /**
     *  Update an existing category.
     * @param mixed $data
     * @param \App\Models\Category $category
     * @return Category|null
     */
    public function updateCategory($data, Category $category)
    {
        try {
            $category->update(   array_filter([
                'name' => $data['name'],
                'image' => FileStorage::fileExists($data['image'] ?? null, $category->image, 'Category', 'img')
            ]));
            return $category;
        } catch (\Throwable $th) {
            Log::error($th);
            $this->throwExceptionJson();
        }
    }
}
