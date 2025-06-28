<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
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
            'code' => 'nullable|string|unique:coupons,code|max:8',
            'discount_percentage' => 'nullable|numeric|min:1|max:100',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable',
        ];
    }
}
