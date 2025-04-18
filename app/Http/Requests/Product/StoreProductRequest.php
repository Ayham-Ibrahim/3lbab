<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'category_id'     => 'required|integer|exists:categories,id',
            'store_id'        => 'required|integer|exists:stores,id',
            'price'           => 'required|numeric',
            'name'            => 'required|string',
            'description'     => 'required|string',
            'video'           => 'required|file|mimetypes:video/mp4,video/quicktime|max:10240',
            'is_available'    => 'required|boolean',
            'images'          => 'required|array',
            'images.*.file'   => 'required|file|image|mimes:png,jpg,jpeg,gif|max:10000|mimetypes:image/jpeg,image/png,image/jpg,image/gif',
        ];
    }
}
