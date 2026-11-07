<?php

namespace App\Modules\Catalog\Requests;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminInventoryAdjustRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'delta.required' => 'Độ lệch tồn kho delta là bắt buộc',
            'delta.integer' => 'Độ lệch tồn kho phải là số nguyên',
            'delta.not_in' => 'Độ lệch tồn kho delta phải khác 0',
            'reason.required' => 'Lý do điều chỉnh kho là bắt buộc',
            'reason.max' => 'Lý do điều chỉnh kho tối đa 255 ký tự',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ', 422, $validator->errors()->toArray())
        );
    }
}
