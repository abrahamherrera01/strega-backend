<?php

namespace App\Http\Requests;

use App\Http\Requests\CustomerStoreRequest;

class CustomerUpdateRequest extends CustomerStoreRequest
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
        $rules = parent::rules();
        
        $rules['full_name'] = 'sometimes|string';

        return $rules;
    }

}
