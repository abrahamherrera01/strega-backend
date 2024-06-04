<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;

class CustomerStoreRequest extends FormRequest
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
            'id_client_bp' => 'required|string',
            'rfc' => 'nullable|string',
            'tax_regime' => 'nullable|string',
            'full_name' => 'required|string',
            'gender' => 'nullable|string',
            'contact_method' => 'nullable|string',
            'phone_1' => 'nullable|string',
            'phone_2' => 'nullable|string',
            'phone_3' => 'nullable|string',
            'cellphone' => 'nullable|string',
            'email_1' => 'nullable|string',
            'email_2' => 'nullable|string',
            'city' => 'nullable|string',
            'delegacy' => 'nullable|string',
            'colony' => 'nullable|string',
            'address' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'type' => 'nullable|string',
            'picture' => 'nullable|string',
            'user_id' => 'nullable|integer',
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
