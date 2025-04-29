<?php

namespace App\Http\Requests\Store;

use App\Http\Requests\BaseFormRequest;
use App\Models\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreStoreRequest extends BaseFormRequest
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
            'manager_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if (Store::where('manager_id', $value)->exists()) {
                        $fail('هذا المدير لديه متجر بالفعل ولا يمكنه إدارة أكثر من متجر واحد.');
                    }
                }
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:stores,name'
            ],
            'description' => [
                'required',
                'string',
                'max:1000'
            ],
            'logo' => [
                'required',
                'image',
                'mimes:png,jpg,jpeg',
                'mimetypes:image/jpeg,image/png,image/jpg',
                'max:10000'
            ],
            'cover' => [
                'required',
                'image',
                'mimes:png,jpg,jpeg',
                'mimetypes:image/jpeg,image/png,image/jpg',
                'max:10000'
            ],
            'location' => [
                'required',
                'string',
                'max:255'
            ],
            'phones' => [
                'required',
                'string',
                'regex:/^(\+9639[0-9]{8})(,\+9639[0-9]{8}){0,2}$/',
                function ($attribute, $value, $fail) {
                    $phones = explode(',', $value);
                    foreach ($phones as $phone) {
                        if (Store::whereJsonContains('phones', $phone)->exists()) {
                            $fail("رقم الهاتف {$phone} مستخدم من قبل متجر آخر.");
                        }
                    }
                }
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'unique:stores,email'
            ],
            'facebook_link' => [
                'nullable',
                'url',
                'max:255'
            ],
            'instagram_link' => [
                'nullable',
                'url',
                'max:255'
            ],
            'youtube_link' => [
                'nullable',
                'url',
                'max:255'
            ],
            'whatsup_link' => [
                'nullable',
                'url',
                'max:255'
            ],
            'telegram_link' => [
                'nullable',
                'url',
                'max:255'
            ],
            'categories' => [
                'nullable',
                'array'
            ],
            'categories.*' => [
                'integer',
                'exists:categories,id'
            ]
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'phones' => str_replace(['"', "'", ' '], '', $this->phones),
        ]);

        if (!$this->has('manager_id') && Auth::check()) {
            $this->merge([
                'manager_id' => Auth::id()
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'manager_id' => 'مدير المتجر',
            'name' => 'اسم المتجر',
            'description' => 'وصف المتجر',
            'logo' => 'شعار المتجر',
            'cover' => 'صورة الغلاف',
            'location' => 'الموقع',
            'phones' => 'أرقام الهواتف',
            'email' => 'البريد الإلكتروني',
            'facebook_link' => 'رابط فيسبوك',
            'instagram_link' => 'رابط إنستغرام',
            'youtube_link' => 'رابط يوتيوب',
            'whatsup_link' => 'رابط واتساب',
            'telegram_link' => 'رابط تلغرام'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'حقل :attribute مطلوب.',
            'manager_id.unique' => 'هذا المدير لديه متجر بالفعل ولا يمكنه إدارة أكثر من متجر واحد.',
            'string' => 'حقل :attribute يجب أن يكون نصاً.',
            'max' => 'حقل :attribute يجب ألا يتجاوز :max حرف/أحرف.',
            'email' => 'حقل :attribute يجب أن يكون بريداً إلكترونياً صالحاً.',
            'unique' => 'قيمة :attribute مسجلة مسبقاً.',
            'url' => 'حقل :attribute يجب أن يكون رابطاً صالحاً.',
            'integer' => 'حقل :attribute يجب أن يكون رقماً صحيحاً.',
            'exists' => 'قيمة :attribute غير موجودة في السجلات.',

            'phones.regex' => 'صيغة أرقام الهواتف غير صالحة. يجب أن تكون بالشكل: +963955555555 أو +963955555555,+963944444444 (بحد أقصى 3 أرقام مفصولة بفواصل)',
            'phones.unique' => 'رقم الهاتف مستخدم من قبل متجر آخر.',

            'logo.required' => 'حقل شعار المتجر مطلوب.',
            'logo.image' => 'حقل شعار المتجر يجب أن يكون صورة.',
            'logo.mimes' => 'شعار المتجر يجب أن يكون من نوع: png, jpg, jpeg, gif.',
            'logo.max' => 'حجم شعار المتجر يجب ألا يتجاوز 10 ميجابايت.',

            'cover.required' => 'حقل صورة الغلاف مطلوب.',
            'cover.image' => 'حقل صورة الغلاف يجب أن يكون صورة.',
            'cover.mimes' => 'صورة الغلاف يجب أن تكون من نوع: png, jpg, jpeg, gif.',
            'cover.max' => 'حجم صورة الغلاف يجب ألا يتجاوز 10 ميجابايت.',

            'logo.mimetypes' => 'نوع ملف شعار المتجر غير مسموح به. الأنواع المسموحة: image/jpeg, image/png, image/jpg, image/gif.',
            'cover.mimetypes' => 'نوع ملف صورة الغلاف غير مسموح به. الأنواع المسموحة: image/jpeg, image/png, image/jpg, image/gif.',

            'categories.array' => 'يجب أن تكون الفئات مصفوفة',
            'categories.*.integer' => 'يجب أن يكون معرف الفئة رقم صحيح',
            'categories.*.exists' => 'الفئة المحددة غير موجودة'
        ];
    }
}
