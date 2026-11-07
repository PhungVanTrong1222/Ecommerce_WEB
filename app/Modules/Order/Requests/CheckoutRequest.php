<?php

namespace App\Modules\Order\Requests;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullname' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'payment_method' => ['required', 'string', 'in:COD,VNPay'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'fullname.required' => 'Họ tên người nhận là bắt buộc',
            'fullname.max' => 'Họ tên không được vượt quá 150 ký tự',
            'phone.required' => 'Số điện thoại là bắt buộc',
            'phone.max' => 'Số điện thoại không được vượt quá 20 ký tự',
            'address.required' => 'Địa chỉ nhận hàng là bắt buộc',
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự',
            'payment_method.required' => 'Phương thức thanh toán là bắt buộc',
            'payment_method.in' => 'Phương thức thanh toán phải là COD hoặc VNPay',
            'note.max' => 'Ghi chú không được vượt quá 500 ký tự',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ', 422, $validator->errors()->toArray())
        );
    }
}
