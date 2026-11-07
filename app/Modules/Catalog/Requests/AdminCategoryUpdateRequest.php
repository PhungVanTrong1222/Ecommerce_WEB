<?php

namespace App\Modules\Catalog\Requests;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminCategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:Active,Inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Tên danh mục tối đa 100 ký tự',
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
