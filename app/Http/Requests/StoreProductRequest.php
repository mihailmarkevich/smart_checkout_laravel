<?php

namespace App\Http\Requests;

class StoreProductRequest extends ApiFormRequest
{

    public function rules(): array
    {
        return [
            'sku'           => ['required', 'string', 'max:255', 'unique:products,sku'],
            'name'          => ['required', 'string', 'max:255'],
            'price'         => ['required', 'numeric', 'min:0'],
            'available_qty' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.required'   => 'SKU is required.',
            'sku.unique'     => 'SKU must be unique.',
            'name.required'  => 'Product name is required.',
            'price.required' => 'Price is required.',
            'price.numeric'  => 'Price must be a number.',
        ];
    }
}
