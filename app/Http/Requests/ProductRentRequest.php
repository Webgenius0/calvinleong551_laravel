<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRentRequest extends FormRequest
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
            // 'renter_id' => ['required', 'exists:users,id'],
            // 'owner_id' => ['required', 'exists:users,id', 'different:renter_id'],
            'product_id' => ['required', 'exists:products,id'],
            // 'rental_price' => ['required', 'numeric', 'min:0'],
            // 'status' => ['required', 'in:pending,accepted,rejected,noresponse'],
            'description' => 'nullable|string',
            'expected_return_date' => ['nullable', 'date', 'after_or_equal:today'],
            'rented_product_image' => ['nullable', 'image', 'max:2048'], // max 2MB
            'returned_product_image' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
