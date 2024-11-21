<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'phonenumber' => 'sometimes|required|string|regex:/^\d{10}$/',
            'address' => 'sometimes|required|string|max:255',
            'totalprice' =>'sometimes|required|numeric|gt:0',
            'status' => 'sometimes|required|in:pending,cancelled,delivery,success',
            'payment_status' => 'sometimes|required|in:unpaid,paid,cancelled'
        ];
    }
}
