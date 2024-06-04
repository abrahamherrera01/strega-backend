<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;

class OrderStoreRequest extends FormRequest
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
            'id_order_bp' => 'required|string',
            'service_date' => 'nullable|string',
            'service_billing_date' => 'nullable|string',
            'sale_billing_date' => 'nullable|string',
            'gross_price' => 'required|numeric',
            'tax_price' => 'required|numeric',
            'total_price' => 'required|numeric',
            'order_km' => 'required|numeric',
            'observations' => 'nullable|string',
            'order_type' => 'nullable|string',
            'order_category' => 'nullable|string',
            'customer_id' => 'nullable|string',
            'customer_fact_id' => 'nullable|string',
            'customer_contact_id' => 'nullable|string',
            'customer_legal_id' => 'nullable|string',
            'vehicle_id' => 'nullable|numeric',
            'sales_executive_id' => 'nullable|numeric',
            'branch_id' => 'nullable|numeric',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'status' => 'error',
            'code'   => 422,
            'errors' => $validator->errors()->all(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
