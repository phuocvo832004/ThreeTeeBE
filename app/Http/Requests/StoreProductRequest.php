<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:30',
            'amount' => 'required|integer',
            'description' => 'nullable|string',
            'create' => 'required|date',
            'sold' => 'integer|nullable',
            'price' => 'required|integer',
            'size' => 'required|integer',
            'rate' => 'numeric|nullable|min:0|max:5',
        ];
    }
}
