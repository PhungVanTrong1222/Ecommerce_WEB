<?php

namespace App\Modules\Catalog\Requests;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminVariantUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_name' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0.01'],
            'sale_price' => ['nullable', 'numeric', 'min:0.01'],
            'status' => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.min' => 'Giá của biến thể phải lớn hơn 0',
            'status.in' => 'Trạng thái phải là Active hoặc Inactive',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ', 422, $validator->errors()->toArray())
        );
    }
}
