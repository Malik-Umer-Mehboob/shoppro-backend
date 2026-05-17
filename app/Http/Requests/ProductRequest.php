<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization is handled in the Service/Policy
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name'                => ($isUpdate ? 'sometimes|' : 'required|') . 'string|max:255',
            'description'         => 'nullable|string',
            'short_description'   => 'nullable|string|max:500',
            'price'               => ($isUpdate ? 'sometimes|' : 'required|') . 'numeric|min:0',
            'sale_price'          => 'nullable|numeric|min:0',
            'category_id'         => [
                $isUpdate ? 'sometimes' : 'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $user = $this->user();
                    if ($user && $user->hasRole('seller')) {
                        $isAssigned = $user->assignedCategories()->where('categories.id', $value)->exists();
                        if (!$isAssigned) {
                            $fail('You are not authorized to upload products in this category.');
                        }
                    }
                },
            ],
            'stock_quantity'      => ($isUpdate ? 'sometimes|' : 'required|') . 'integer|min:0',
            'low_stock_threshold' => [
                'nullable',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) {
                    $stock = (int) $this->input('stock_quantity', $this->product?->stock_quantity ?? 0);
                    if ($value !== null && $value >= $stock) {
                        $fail('Low stock threshold must be less than stock quantity.');
                    }
                },
            ],
            'status'              => 'nullable|in:draft,published,archived',
            'is_featured'         => 'nullable|boolean',
            'thumbnail'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'tags'                => 'nullable|array',
            'tags.*'              => 'string',
        ];
    }
}
