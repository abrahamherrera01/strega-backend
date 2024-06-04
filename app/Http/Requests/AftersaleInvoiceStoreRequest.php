<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;

use Illuminate\Http\Response;

use App\Http\Validation\Rules\AftersalesInvoiceRules;

class AftersaleInvoiceStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'invoices' => 'required|array', 
            'branch' => 'required',
            'total' => 'required',
        ];

    }

    protected function failedValidation(Validator $validator)
    {    
        throw new ValidationException($validator, response()->json([
            'status' => 'error',
            'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'message' => 'Validation error',
            'errors' => $validator->errors()->toArray()
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }


    public function validateInvoices()
    {
        $invoices = $this->input('invoices');
        $validatedInvoices = [];
        $invalidInvoices = [];

        foreach ($invoices as $index => $invoice) {
            $validator = new AftersalesInvoiceRules();
            $validated = $validator->passes('invoices', $invoice);

            if ($validated) {
                $validatedInvoices[] = $invoice;
            } else {
                $invalidInvoices[] = [
                    'index' => $index,
                    'errors' => $validator->message()
                ];
            }
        }

        return ['valid' => $validatedInvoices, 'invalid' => $invalidInvoices];
    }
}