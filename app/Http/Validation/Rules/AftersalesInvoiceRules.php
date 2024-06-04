<?php

namespace App\Http\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class AftersalesInvoiceRules implements Rule
{
    protected $errors = [];

    public function passes($attribute, $invoice)
    {
        $orderDetailsValid = $this->validateOrderDetails($invoice);
        $clientDetailsValid = $this->validateClientDetails($invoice);
        $clientFactDetailsValid = $this->validateClientFactDetails($invoice);
        $vehicleDetailsValid = $this->validateVehicleDetails($invoice);

        return $orderDetailsValid && $clientDetailsValid && $clientFactDetailsValid && $vehicleDetailsValid;
    }

    public function message()
    {
        $errorMessages = [];

        foreach ($this->errors as $field => $error) {
            $errorMessages[] = "$field: $error";
        }

        return "Invoice validation failed. " . implode(', ', $errorMessages);
        }

    public function errors()
    {
        return $this->errors;
    }

    protected function validateOrderDetails($invoice)
    {
        // Example validation rules for order details
        $isValid = isset($invoice['id_order_bp']) &&
            isset($invoice['service_billing_date']) &&
            isset($invoice['service_date']) &&
            isset($invoice['gross_price']) &&
            isset($invoice['tax_price']) &&
            isset($invoice['total_price']) &&
            isset($invoice['order_km']) &&
            isset($invoice['id_sales_executive_bp']) &&
            isset($invoice['full_name_sales_executive']) &&
            isset($invoice['order_type']);

        if (!$isValid) {
            $this->errors['Order_details'] = 'Order details are invalid';
        }

        return $isValid;
    }

    protected function validateClientDetails($invoice)
    {
        // Example validation rules for client details
        $isValid = isset($invoice['id_client_bp']) &&
            isset($invoice['full_name']) &&
            isset($invoice['type']);

        if (!$isValid) {

            $this->errors['Client_details'] = 'Client details are invalid';
        }

        return $isValid;
    }

    protected function validateClientFactDetails($invoice)
    {
        // Example validation rules for client fact details
        $isValid = isset($invoice['id_client_fact_bp']) &&
            isset($invoice['fact_rfc']) &&
            isset($invoice['fact_full_name']) &&
            isset($invoice['fact_type']);

        if (!$isValid) {

            $this->errors['Client_fact_details'] = 'Client fact details are invalid';
        }

        return $isValid;
    }

    protected function validateVehicleDetails($invoice)
    {
        // Example validation rules for vehicle details
        $isValid = isset($invoice['vin']);

        if (!$isValid) {
            $this->errors['Vehicle_details'] = 'Vehicle details are invalid';
        }

        return $isValid;
    }
}