<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $hasColors = $this->variants->where('color_id', '!=', null)->count() > 0;
        $hasSizes = $this->variants->where('size_id', '!=', null)->count() > 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category->name,
            'store' => $this->store->name,
            'description' => $this->description,
            'price' => $this->price,
            'has_colors' => $hasColors,
            'has_sizes' => $hasSizes,
            'media' => [
                'video' => $this->video,
                'images' => $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'image' => $image->image
                    ];
                })
            ],
            'colors' => $this->when($hasColors, function () {
                $colors = [];
                $variantsWithColors = $this->variants->where('color_id', '!=', null);

                foreach ($variantsWithColors->groupBy('color_id') as $colorVariants) {
                    $color = $colorVariants->first()->color;
                    $sizes = [];
                    $quantityWithoutSize = 0;

                    foreach ($colorVariants as $variant) {
                        if ($variant->size_id) {
                            $sizes[] = new SizeResource($variant->size, $variant->quantity);
                        } else {
                            $quantityWithoutSize += $variant->quantity;
                        }
                    }

                    $colors[] = [
                        'id' => $color->id,
                        'name' => $color->name,
                        'hex_code' => $color->hex_code,
                        'sizes' => $sizes,
                        'quantity_without_size' => $quantityWithoutSize
                    ];
                }

                return $colors;
            }),
            'sizes' => $this->when($hasSizes, function () {
                $sizes = [];
                $variantsWithSizes = $this->variants->where('size_id', '!=', null)
                    ->where('color_id', null);

                foreach ($variantsWithSizes as $variant) {
                    $sizes[] = new SizeResource($variant->size, $variant->quantity);
                }

                return $sizes;
            }),
            'quantity_without_variants' => $this->when(
                $this->variants->where('color_id', null)->where('size_id', null)->count() > 0,
                function () {
                    return $this->variants->where('color_id', null)
                        ->where('size_id', null)
                        ->sum('quantity');
                }
            )
        ];
    }
}
