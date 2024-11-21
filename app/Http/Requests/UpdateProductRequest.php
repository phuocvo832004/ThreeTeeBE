<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules()
    {
        return [
            'name' => 'string|max:30',
            'amount' => 'integer',
            'description' => 'nullable|string',
            'create' => 'date',
            'sold' => 'integer|nullable',
            'price' => 'integer',
            'size' => 'integer',
            'rate' => 'numeric|nullable|min:0|max:5',
        ];
    }
}
