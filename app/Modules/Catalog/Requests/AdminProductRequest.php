<?php

namespace App\Modules\Catalog\Requests;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable'],
            'status' => ['nullable', 'string', 'in:Active,Inactive'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.variant_name' => ['required', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0.01'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0.01'],
            'variants.*.initial_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Danh mục sản phẩm là bắt buộc',
            'name.required' => 'Tên sản phẩm là bắt buộc',
            'name.max' => 'Tên sản phẩm tối đa 255 ký tự',
            'variants.required' => 'Cần ít nhất một biến thể sản phẩm',
            'variants.min' => 'Cần ít nhất một biến thể sản phẩm',
            'variants.*.sku.required' => 'SKU của biến thể là bắt buộc',
            'variants.*.variant_name.required' => 'Tên biến thể là bắt buộc',
            'variants.*.price.required' => 'Giá của biến thể là bắt buộc',
            'variants.*.price.min' => 'Giá của biến thể phải lớn hơn 0',
            'variants.*.initial_quantity.required' => 'Số lượng tồn kho ban đầu là bắt buộc',
            'variants.*.initial_quantity.min' => 'Số lượng tồn kho không được âm',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ', 422, $validator->errors()->toArray())
        );
    }
}
