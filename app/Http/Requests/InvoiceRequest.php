<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAccountant();
    }

    public function rules(): array
    {
        return [
            'customer_id'            => 'required|exists:customers,id',
            'reference'              => 'nullable|string|max:100',
            'invoice_date'           => 'required|date',
            'due_date'               => 'required|date|after_or_equal:invoice_date',
            'vat_applicable'         => 'boolean',
            'wht_applicable'         => 'boolean',
            'wht_rate'               => 'nullable|numeric|min:0|max:30',
            'discount_amount'        => 'nullable|numeric|min:0',
            'notes'                  => 'nullable|string|max:1000',
            'terms'                  => 'nullable|string|max:1000',

            // Line items
            'items'                  => 'required|array|min:1',
            'items.*.description'    => 'required|string|max:500',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.vat_applicable' => 'boolean',
            'items.*.account_code'   => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'        => 'Please select a customer.',
            'items.required'              => 'At least one line item is required.',
            'items.min'                   => 'At least one line item is required.',
            'due_date.after_or_equal'     => 'Due date must be on or after the invoice date.',
        ];
    }
}
