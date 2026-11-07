<?php

namespace App\Modules\Catalog\Requests;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:100'],
            'variant_name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'sale_price' => ['nullable', 'numeric', 'min:0.01'],
            'initial_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'SKU của biến thể là bắt buộc',
            'variant_name.required' => 'Tên biến thể là bắt buộc',
            'price.required' => 'Giá của biến thể là bắt buộc',
            'price.min' => 'Giá của biến thể phải lớn hơn 0',
            'initial_quantity.required' => 'Số lượng tồn kho ban đầu là bắt buộc',
            'initial_quantity.min' => 'Số lượng tồn kho không được âm',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ', 422, $validator->errors()->toArray())
        );
    }
}
