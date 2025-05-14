<?php

namespace App\Http\Requests\Offer;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
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
            'description' => 'required|string',
            'store_id' => 'required|exists:stores,id',
            'discount_percentage' => 'required|numeric|min:0|max:100',
            'image' => 'required|file|image|mimes:png,jpg,jpeg|max:10000|mimetypes:image/jpeg,image/png,image/jpg',
            'starts_at' => 'required|date|before_or_equal:ends_at',
            'ends_at' => 'required|date|after_or_equal:starts_at',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
        ];
    }
}
