<?php

namespace App\Http\Requests\Offer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => 'nullable|string',
            'store_id' => 'nullable|exists:stores,id',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'image' => 'nullable|file|image|mimes:png,jpg,jpeg|max:10000|mimetypes:image/jpeg,image/png,image/jpg',
            'starts_at' => 'nullable|date|before_or_equal:ends_at',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
        ];
    }
}
