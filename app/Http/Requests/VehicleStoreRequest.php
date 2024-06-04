<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;

class VehicleStoreRequest extends FormRequest
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
            'id_vehicle_bp' => 'nullable|string',
            'name' => 'nullable|string',
            'vin' => 'required|string',
            'description' => 'nullable|string',
            'model' => 'nullable|string',
            'brand' => 'nullable|string',
            'body' => 'nullable|string',
            'km' => 'nullable|integer',
            'plates' => 'nullable|string',
            'price' => 'nullable|numeric',
            'purchase_date' => 'nullable|string',
            'year_model' => 'nullable|integer',
            'cylinders' => 'nullable|integer',
            'exterior_color' => 'nullable|string',
            'interior_color' => 'nullable|string',
            'transmission' => 'nullable|string',
            'drive_train' => 'nullable|string',
            'location' => 'nullable|string',
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


