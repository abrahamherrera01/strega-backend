<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\AftersaleInvoiceStoreRequest;
use App\Http\Requests\SaleInvoiceStoreRequest;
use App\Http\Requests\PreownedInvoiceStoreRequest;


use App\Models\Order;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\SalesExecutive;
use App\Models\Branch;


class InvoicesController extends Controller
{
    public function storeAftersaleInvoices(AftersaleInvoiceStoreRequest $request)
    {
        
        $validatedInvoices = $request->validateInvoices();

        $validOrderIds = [];

        foreach ($validatedInvoices['valid'] as $invoice) {

            $validOrderIds[] = $invoice['id_order_bp'];

            $orderKeys = ['id_order_bp', 'service_billing_date', 'service_date', 'gross_price', 'tax_price', 'total_price', 'order_km', 'observations', 'order_type'];

            $orderSubset = array_intersect_key($invoice, array_flip($orderKeys));

            $vehicleKeys = ['id_vehicle_bp', 'name', 'vin', 'model', 'km', 'plates', 'cylinders', 'transmission', 'drive_train', 'location'];

            $vehicleSubset = array_intersect_key($invoice, array_flip($vehicleKeys));

            $clientKeys = ['id_client_bp', 'rfc', 'tax_regime', 'full_name', 'gender', 'contact_method', 'phone_1', 'phone_2', 'email_1', 'cellphone', 'city', 'delegacy', 'colony', 'address', 'zip_code', 'type'];

            $clientSubset = array_intersect_key($invoice, array_flip($clientKeys));

            $billerKeys = ['id_client_fact_bp', 'fact_rfc', 'fact_tax_regime', 'fact_full_name', 'fact_gender', 'fact_contact_method', 'fact_phone_1', 'fact_phone_2', 'fact_email_1', 'fact_cellphone', 'fact_city', 'fact_delegacy', 'fact_colony', 'fact_address', 'fact_zip_code', 'fact_type'];

            $billerSubset =  $this->renameKeysAndIntersect($invoice, $billerKeys, 'fact_', '');

            $salesExecutiveKeys = ['id_sales_executive_bp', 'full_name_sales_executive'];

            $salesExecutiveSubset = $this->renameKeysAndIntersect($invoice, $salesExecutiveKeys, 'full_name_sales_executive', 'name');

            try {
        
                DB::beginTransaction();

                // Format date fields if they exist
                $this->formatDateFields($orderSubset, ['service_date', 'service_billing_date', 'sale_billing_date']);

                // Format price fields if they exist
                $this->formatNumberFields($orderSubset, ['gross_price', 'tax_price', 'total_price']);
    
                // Retrieve existing order based on id_order_bp
                $order = Order::firstOrNew(['id_order_bp' => $orderSubset['id_order_bp']]);
                
                // Extract the corresponding subset of values from the $order array
                $subsetOrderData = array_intersect_key($order->toArray(), array_flip($orderKeys));

                // Compare and update only changed fields
                $orderChangedData = array_diff_assoc($orderSubset, $subsetOrderData);

                // Remove null values and empty strings from the changed data
                $orderChangedData = array_filter($orderChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($orderChangedData)) {

                    // Fill data
                    $order->fill($orderChangedData);
    
                    // Save the order
                    $order->save();
    
                    Log::info('Order saved successfully', ['id_order_bp' => $order->id_order_bp]);

                }  else {

                    Log::info('Order exists and is not updated: ', ['id_order_bp' => $order->id_order_bp]);
                }

                // Format date fields if they exist
                $this->formatDateFields($vehicleSubset, ['purchase_date']);

                // Retrieve existing vehicle based on vin
                $vehicle = Vehicle::firstOrNew(['vin' => $vehicleSubset['vin']]);

                // Compare and update only changed fields
                $vehicleChangedData = array_diff_assoc($vehicleSubset, $vehicle->toArray());
                
                // Remove null values and empty strings from the changed data
                $vehicleChangedData = array_filter($vehicleChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($vehicleChangedData)) {
                    
                    // Fill data
                    $vehicle->fill($vehicleChangedData);

                    // Save the vehicle
                    $vehicle->save();

                    Log::info('Vehicle saved successfully', ['vin' => $vehicle->vin]);

                } else {

                    Log::info('Vehicle exists and is not updated: ', ['vin' => $vehicle->vin]);
                }

                // Link vehicle to order
                if (!$order->vehicle()->where('id', $vehicle->id)->exists()) {

                    $vehicle->orders()->save($order);

                    Log::info('Order linked to vehicle successfully', [
                        'order_id' => $order->id,
                        'vehicle_id' => $vehicle->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to vehicle and is not updated', [
                        'order_id' => $order->id,
                        'vehicle_id' => $vehicle->id,
                    ]);
                }

                // Retrieve existing customer based on id_client_bp - Client segment
                $client = Customer::firstOrNew(['id_client_bp' => $clientSubset['id_client_bp'], 'type' => 'Client']);
                
                // Compare and update only changed fields
                $clientChangedData = array_diff_assoc($clientSubset, $client->toArray());

                // Remove null values and empty strings from the changed data
                $clientChangedData = array_filter($clientChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($clientChangedData)) {

                    // Fill data
                    $client->fill($clientChangedData);

                    // Save the customer - Client
                    $client->save();

                    Log::info('Customer saved successfully', ['id_client_bp' => $client->id_client_bp]);
                    
                } else {

                    Log::info('Customer exists and is not updated', ['id_client_bp' => $client->id_client_bp]);

                }

                // Link client to order
                if (!$order->customer()->where('id', $client->id)->exists()) {

                    $client->orders()->save($order);

                    Log::info('Order linked to client successfully', [
                        'order_id' => $order->id,
                        'client_id' => $client->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to client and is not updated', [
                        'order_id' => $order->id,
                        'client_id' => $client->id,
                    ]);
                }
                // End Client segment


                // Retrieve existing customer based on id_client_bp - Client biller segment
                $biller = Customer::firstOrNew(['id_client_bp' => $billerSubset['id_client_bp'], 'type' => 'Biller']);
                
                // Compare and update only changed fields
                $billerChangedData = array_diff_assoc($billerSubset, $biller->toArray());

                // Remove null values and empty strings from the changed data
                $billerChangedData = array_filter($billerChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($billerChangedData)) {

                    // Fill data
                    $biller->fill($billerChangedData);

                    // Save the customer - Biller
                    $biller->save();

                    Log::info('Customer saved successfully', ['id_biller_bp' => $biller->id_client_bp]);
                    
                } else {

                    Log::info('Customer exists and is not updated', ['id_biller_bp' => $biller->id_client_bp]);

                }

                // Link biller to order
                if (!$order->biller()->where('id', $biller->id)->exists()) {

                    $biller->orders_fact()->save($order);

                    Log::info('Order linked to biller successfully', [
                        'order_id' => $order->id,
                        'biller_id' => $biller->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to biller and is not updated', [
                        'order_id' => $order->id,
                        'biller_id' => $biller->id,
                    ]);
                }
                // End Client biller segment


                // Retrieve existing sales executive based on id_sales_executive_bp
                $salesExecutive = SalesExecutive::firstOrNew(['id_sales_executive_bp' => $salesExecutiveSubset['id_sales_executive_bp']]);

                // Compare and update only changed fields
                $salesExecutiveChangedData = array_diff_assoc($salesExecutiveSubset, $salesExecutive->toArray());

                // Remove null values and empty strings from the changed data
                $salesExecutiveChangedData = array_filter($salesExecutiveChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($salesExecutiveChangedData)) {
                    $salesExecutive->fill($salesExecutiveChangedData);

                    // Save the sales executive
                    $salesExecutive->save();

                    Log::info('Sales executive saved successfully', ['id_sales_executive_bp' => $salesExecutive->id_sales_executive_bp]);

                } else {

                    Log::info('Sales executive exists and is not updated', ['id_sales_executive_bp' => $salesExecutive->id_sales_executive_bp]);

                }

                // Link sales executive to order
                if (!$order->salesExecutive()->where('id', $salesExecutive->id)->exists()) {

                    $salesExecutive->orders()->save($order);

                    Log::info('Order linked to sales executive successfully', [
                        'order_id' => $order->id,
                        'sales_executive_id' => $salesExecutive->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to sales executive and is not updated', [
                        'order_id' => $order->id,
                        'sales_executive_id' => $salesExecutive->id,
                    ]);
                }

                // End sales executive segment


                // Retrieve existing branch based on name or create a new one
                $branch = Branch::firstOrNew(['name' => $request->branch]);
                
                $branch->save();
                    
                Log::info('Branch saved successfully', ['branch_id' => $branch->id]);


                // Link branch to order
                if (!$order->branch()->where('id', $branch->id)->exists()) {

                    $branch->orders()->save($order);

                    Log::info('Order linked to branch successfully', [
                        'order_id' => $order->id,
                        'branch_id' => $branch->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to branch and is not updated', [
                        'order_id' => $order->id,
                        'branch_id' => $branch->id,
                    ]);
                }

                // End branch segment

                //Link vehicle with client
                // Check if the relationship already exists
                if (!$client->vehicles->contains($vehicle->id)) {
                    $client->vehicles()->attach($vehicle->id);
        
                    Log::info('Customer linked to vehicle successfully', ['client_id' => $client->id, 'vehicle_id' => $vehicle->id]);

                } else {

                    Log::info('Customer is already linked to this vehicle', ['client_id' => $client->id, 'vehicle_id' => $vehicle->id]);

                }

                // End Link Vehicle with client segment
    
                DB::commit();
            
            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Failed to save order: ',['error_message' => $e->getMessage()]);
    
                return response()->json([
                    'status' => 'error',
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'An error occurred during processing order'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        }

        return response()->json([
            'status' => 'success',
            'code' => Response::HTTP_OK,
            'message' => 'Invoices stored successfully',
            'totalInvoices' => $request->total,
            'totalValid' => count($validatedInvoices['valid']),
            'valid_invoices' => $validOrderIds,
            'totalInvalid' => count($validatedInvoices['invalid']),
            'invalid_invoices' => $validatedInvoices['invalid']
        ], Response::HTTP_OK);
    }


    public function storeSaleInvoices(SaleInvoiceStoreRequest $request)
    {
        
        $validatedInvoices = $request->validateInvoices();

        $validOrderIds = [];

        foreach ($validatedInvoices['valid'] as $invoice) {

            $validOrderIds[] = $invoice['id_order_bp'];

            $orderKeys = ['id_order_bp', 'sale_billing_date', 'gross_price', 'tax_price', 'total_price', 'order_km', 'observations', 'order_type'];

            $orderSubset = array_intersect_key($invoice, array_flip($orderKeys));

            $vehicleKeys = ['id_vehicle_bp', 'name', 'vin', 'model', 'km', 'plates', 'cylinders', 'transmission', 'drive_train', 'location'];

            $vehicleSubset = array_intersect_key($invoice, array_flip($vehicleKeys));

            $clientKeys = ['id_client_bp', 'rfc', 'tax_regime', 'full_name', 'gender', 'contact_method', 'phone_1', 'phone_2', 'email_1', 'cellphone', 'city', 'delegacy', 'colony', 'address', 'zip_code', 'type'];

            $clientSubset = array_intersect_key($invoice, array_flip($clientKeys));

            $billerKeys = ['id_client_fact_bp', 'fact_rfc', 'fact_tax_regime', 'fact_full_name', 'fact_gender', 'fact_contact_method', 'fact_phone_1', 'fact_phone_2', 'fact_email_1', 'fact_cellphone', 'fact_city', 'fact_delegacy', 'fact_colony', 'fact_address', 'fact_zip_code', 'fact_type'];

            $billerSubset =  $this->renameKeysAndIntersect($invoice, $billerKeys, 'fact_', '');

            $contactKeys = ['id_client_contact__bp', 'contact__rfc', 'contact__tax_regime', 'contact__full_name', 'contact__gender', 'contact__contact_method', 'contact__phone_1', 'contact__phone_2', 'contact__email_1', 'contact__cellphone', 'contact__city', 'contact__delegacy', 'contact__colony', 'contact__address', 'contact__zip_code', 'contact__type'];

            $contactSubset =  $this->renameKeysAndIntersect($invoice, $contactKeys, 'contact__', '');

            $legalKeys = ['id_client_legal_bp', 'legal_rfc', 'legal_tax_regime', 'legal_full_name', 'legal_gender', 'legal_contact_method', 'legal_phone_1', 'legal_phone_2', 'legal_email_1', 'legal_cellphone', 'legal_city', 'legal_delegacy', 'legal_colony', 'legal_address', 'legal_zip_code', 'legal_type'];

            $legalSubset =  $this->renameKeysAndIntersect($invoice, $legalKeys, 'legal_', '');

            $salesExecutiveKeys = ['id_sales_executive_bp', 'full_name_sales_executive'];

            $salesExecutiveSubset = $this->renameKeysAndIntersect($invoice, $salesExecutiveKeys, 'full_name_sales_executive', 'name');

            try {
        
                DB::beginTransaction();

                // Format date fields if they exist
                $this->formatDateFields($orderSubset, ['service_date', 'service_billing_date', 'sale_billing_date']);

                // Format price fields if they exist
                $this->formatNumberFields($orderSubset, ['gross_price', 'tax_price', 'total_price']);
    
                // Retrieve existing order based on id_order_bp
                $order = Order::firstOrNew(['id_order_bp' => $orderSubset['id_order_bp']]);
                
                // Extract the corresponding subset of values from the $order array
                $subsetOrderData = array_intersect_key($order->toArray(), array_flip($orderKeys));

                // Compare and update only changed fields
                $orderChangedData = array_diff_assoc($orderSubset, $subsetOrderData);

                // Remove null values and empty strings from the changed data
                $orderChangedData = array_filter($orderChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($orderChangedData)) {

                    // Fill data
                    $order->fill($orderChangedData);
    
                    // Save the order
                    $order->save();
    
                    Log::info('Order saved successfully', ['id_order_bp' => $order->id_order_bp]);

                }  else {

                    Log::info('Order exists and is not updated: ', ['id_order_bp' => $order->id_order_bp]);
                }

                // Format date fields if they exist
                $this->formatDateFields($vehicleSubset, ['purchase_date']);

                // Retrieve existing vehicle based on vin
                $vehicle = Vehicle::firstOrNew(['vin' => $vehicleSubset['vin']]);

                // Compare and update only changed fields
                $vehicleChangedData = array_diff_assoc($vehicleSubset, $vehicle->toArray());
                
                // Remove null values and empty strings from the changed data
                $vehicleChangedData = array_filter($vehicleChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($vehicleChangedData)) {
                    
                    // Fill data
                    $vehicle->fill($vehicleChangedData);

                    // Save the vehicle
                    $vehicle->save();

                    Log::info('Vehicle saved successfully', ['vin' => $vehicle->vin]);

                } else {

                    Log::info('Vehicle exists and is not updated: ', ['vin' => $vehicle->vin]);
                }

                // Link vehicle to order
                if (!$order->vehicle()->where('id', $vehicle->id)->exists()) {

                    $vehicle->orders()->save($order);

                    Log::info('Order linked to vehicle successfully', [
                        'order_id' => $order->id,
                        'vehicle_id' => $vehicle->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to vehicle and is not updated', [
                        'order_id' => $order->id,
                        'vehicle_id' => $vehicle->id,
                    ]);
                }

                // Retrieve existing customer based on id_client_bp - Client segment
                $client = Customer::firstOrNew(['id_client_bp' => $clientSubset['id_client_bp'], 'type' => 'Client']);
                
                // Compare and update only changed fields
                $clientChangedData = array_diff_assoc($clientSubset, $client->toArray());

                // Remove null values and empty strings from the changed data
                $clientChangedData = array_filter($clientChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($clientChangedData)) {

                    // Fill data
                    $client->fill($clientChangedData);

                    // Save the customer - Client
                    $client->save();

                    Log::info('Customer saved successfully', ['id_client_bp' => $client->id_client_bp]);
                    
                } else {

                    Log::info('Customer exists and is not updated', ['id_client_bp' => $client->id_client_bp]);

                }

                // Link client to order
                if (!$order->customer()->where('id', $client->id)->exists()) {

                    $client->orders()->save($order);

                    Log::info('Order linked to client successfully', [
                        'order_id' => $order->id,
                        'client_id' => $client->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to client and is not updated', [
                        'order_id' => $order->id,
                        'client_id' => $client->id,
                    ]);
                }
                // End Client segment


                // Retrieve existing customer based on id_client_bp - Client biller segment
                $biller = Customer::firstOrNew(['id_client_bp' => $billerSubset['id_client_bp'], 'type' => 'Biller']);
                
                // Compare and update only changed fields
                $billerChangedData = array_diff_assoc($billerSubset, $biller->toArray());

                // Remove null values and empty strings from the changed data
                $billerChangedData = array_filter($billerChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($billerChangedData)) {

                    // Fill data
                    $biller->fill($billerChangedData);

                    // Save the customer - Biller
                    $biller->save();

                    Log::info('Customer saved successfully', ['id_biller_bp' => $biller->id_client_bp]);
                    
                } else {

                    Log::info('Customer exists and is not updated', ['id_biller_bp' => $biller->id_client_bp]);

                }

                // Link biller to order
                if (!$order->biller()->where('id', $biller->id)->exists()) {

                    $biller->orders_fact()->save($order);

                    Log::info('Order linked to biller successfully', [
                        'order_id' => $order->id,
                        'biller_id' => $biller->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to biller and is not updated', [
                        'order_id' => $order->id,
                        'biller_id' => $biller->id,
                    ]);
                }
                // End Client biller segment


                // Validate Contact information
                if(isset($contactSubset['id_client_bp'])) { 
                    
                    // Retrieve existing customer based on id_client_bp - Client contact segment
                    $contact = Customer::firstOrNew(['id_client_bp' => $contactSubset['id_client_bp'], 'type' => 'Contact']);
                    
                    // Validate Name of customer - Legal

                    if(!isset($contactSubset['full_name'])){
                        $contactSubset['full_name'] = "Sin nombre";
                    }

                    // Compare and update only changed fields
                    $contactChangedData = array_diff_assoc($contactSubset, $contact->toArray());

                    // Remove null values and empty strings from the changed data
                    $contactChangedData = array_filter($contactChangedData, function ($value) {
                        return $value !== null && $value !== '';
                    });

                    // Update only if there are non-null and non-empty string changes
                    if (!empty($contactChangedData)) {

                        // Fill data
                        $contact->fill($contactChangedData);

                        // Save the customer - Contact
                        $contact->save();

                        Log::info('Customer saved successfully', ['id_contact_bp' => $contact->id_client_bp]);
                        
                    } else {

                        Log::info('Customer exists and is not updated', ['id_contact_bp' => $contact->id_client_bp]);

                    }

                    // Link contact to order
                    if (!$order->contact()->where('id', $contact->id)->exists()) {

                        $contact->orders_fact()->save($order);

                        Log::info('Order linked to contact successfully', [
                            'order_id' => $order->id,
                            'contact_id' => $contact->id,
                        ]);

                    } else {
                        
                        Log::info('Order already linked to contact and is not updated', [
                            'order_id' => $order->id,
                            'contact_id' => $contact->id,
                        ]);
                    }
                    // End Client contact segment

                }

                // // Validate Legal information
                
                if(isset($legalSubset['id_client_bp'])) {

                    // Retrieve existing customer based on id_client_bp - Client legal segment
                    $legal = Customer::firstOrNew(['id_client_bp' => $legalSubset['id_client_bp'], 'type' => 'Legal']);
                    
                    // Validate Name of customer - Legal

                    if(!isset($legalSubset['full_name'])){
                        $legalSubset['full_name'] = "Sin nombre";
                    }

                    // Compare and update only changed fields
                    $legalChangedData = array_diff_assoc($legalSubset, $legal->toArray());

                    // Remove null values and empty strings from the changed data
                    $legalChangedData = array_filter($legalChangedData, function ($value) {
                        return $value !== null && $value !== '';
                    });

                    // Update only if there are non-null and non-empty string changes
                    if (!empty($legalChangedData)) {

                        // Fill data
                        $legal->fill($legalChangedData);

                        // Save the customer - Legal
                        $legal->save();

                        Log::info('Customer saved successfully', ['id_legal_bp' => $legal->id_client_bp]);
                        
                    } else {

                        Log::info('Customer exists and is not updated', ['id_legal_bp' => $legal->id_client_bp]);

                    }

                    // Link biller to order
                    if (!$order->legal()->where('id', $legal->id)->exists()) {

                        $legal->orders_fact()->save($order);

                        Log::info('Order linked to legal successfully', [
                            'order_id' => $order->id,
                            'legal_id' => $legal->id,
                        ]);

                    } else {
                        
                        Log::info('Order already linked to legal and is not updated', [
                            'order_id' => $order->id,
                            'legal_id' => $legal->id,
                        ]);
                    }
                    // End Client legal segment

                }

                // Retrieve existing sales executive based on id_sales_executive_bp
                $salesExecutive = SalesExecutive::firstOrNew(['id_sales_executive_bp' => $salesExecutiveSubset['id_sales_executive_bp']]);

                // Compare and update only changed fields
                $salesExecutiveChangedData = array_diff_assoc($salesExecutiveSubset, $salesExecutive->toArray());

                // Remove null values and empty strings from the changed data
                $salesExecutiveChangedData = array_filter($salesExecutiveChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($salesExecutiveChangedData)) {
                    $salesExecutive->fill($salesExecutiveChangedData);

                    // Save the sales executive
                    $salesExecutive->save();

                    Log::info('Sales executive saved successfully', ['id_sales_executive_bp' => $salesExecutive->id_sales_executive_bp]);

                } else {

                    Log::info('Sales executive exists and is not updated', ['id_sales_executive_bp' => $salesExecutive->id_sales_executive_bp]);

                }

                // Link sales executive to order
                if (!$order->salesExecutive()->where('id', $salesExecutive->id)->exists()) {

                    $salesExecutive->orders()->save($order);

                    Log::info('Order linked to sales executive successfully', [
                        'order_id' => $order->id,
                        'sales_executive_id' => $salesExecutive->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to sales executive and is not updated', [
                        'order_id' => $order->id,
                        'sales_executive_id' => $salesExecutive->id,
                    ]);
                }

                // End sales executive segment


                // Retrieve existing branch based on name or create a new one
                $branch = Branch::firstOrNew(['name' => $request->branch]);
                
                $branch->save();
                    
                Log::info('Branch saved successfully', ['branch_id' => $branch->id]);


                // Link branch to order
                if (!$order->branch()->where('id', $branch->id)->exists()) {

                    $branch->orders()->save($order);

                    Log::info('Order linked to branch successfully', [
                        'order_id' => $order->id,
                        'branch_id' => $branch->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to branch and is not updated', [
                        'order_id' => $order->id,
                        'branch_id' => $branch->id,
                    ]);
                }

                // End branch segment

                //Link vehicle with client
                // Check if the relationship already exists
                if (!$client->vehicles->contains($vehicle->id)) {
                    $client->vehicles()->attach($vehicle->id);
        
                    Log::info('Customer linked to vehicle successfully', ['client_id' => $client->id, 'vehicle_id' => $vehicle->id]);

                } else {

                    Log::info('Customer is already linked to this vehicle', ['client_id' => $client->id, 'vehicle_id' => $vehicle->id]);

                }

                // End Link Vehicle with client segment
    
                DB::commit();
            
            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Failed to save order: ',['error_message' => $e->getMessage()]);
    
                return response()->json([
                    'status' => 'error',
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'An error occurred during processing order'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        }

        return response()->json([
            'status' => 'success',
            'code' => Response::HTTP_OK,
            'message' => 'Invoices stored successfully',
            'totalInvoices' => $request->total,
            'totalValid' => count($validatedInvoices['valid']),
            'valid_invoices' => $validOrderIds,
            'totalInvalid' => count($validatedInvoices['invalid']),
            'invalid_invoices' => $validatedInvoices['invalid']
        ], Response::HTTP_OK);
    }

    public function storePreownedInvoices(PreownedInvoiceStoreRequest $request)
    {
        
        $validatedInvoices = $request->validateInvoices();

        $validOrderIds = [];

        foreach ($validatedInvoices['valid'] as $invoice) {

            $validOrderIds[] = $invoice['id_order_bp'];

            $orderKeys = ['id_order_bp', 'sale_billing_date', 'gross_price', 'tax_price', 'total_price', 'order_km', 'observations', 'order_type', 'order_category'];

            $orderSubset = array_intersect_key($invoice, array_flip($orderKeys));

            $vehicleKeys = ['id_vehicle_bp', 'name', 'vin', 'model', 'km', 'plates', 'cylinders', 'transmission', 'drive_train', 'location'];

            $vehicleSubset = array_intersect_key($invoice, array_flip($vehicleKeys));

            $clientKeys = ['id_client_bp', 'rfc', 'tax_regime', 'full_name', 'gender', 'contact_method', 'phone_1', 'phone_2', 'email_1', 'cellphone', 'city', 'delegacy', 'colony', 'address', 'zip_code', 'type'];

            $clientSubset = array_intersect_key($invoice, array_flip($clientKeys));

            $salesExecutiveKeys = ['id_sales_executive_bp', 'full_name_sales_executive'];

            $salesExecutiveSubset = $this->renameKeysAndIntersect($invoice, $salesExecutiveKeys, 'full_name_sales_executive', 'name');

            try {
        
                DB::beginTransaction();

                // Format date fields if they exist
                $this->formatDateFields($orderSubset, ['service_date', 'service_billing_date', 'sale_billing_date']);

                // Format price fields if they exist
                $this->formatNumberFields($orderSubset, ['gross_price', 'tax_price', 'total_price']);
    
                // Retrieve existing order based on id_order_bp
                $order = Order::firstOrNew(['id_order_bp' => $orderSubset['id_order_bp']]);
                
                // Extract the corresponding subset of values from the $order array
                $subsetOrderData = array_intersect_key($order->toArray(), array_flip($orderKeys));

                // Compare and update only changed fields
                $orderChangedData = array_diff_assoc($orderSubset, $subsetOrderData);

                // Remove null values and empty strings from the changed data
                $orderChangedData = array_filter($orderChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($orderChangedData)) {

                    // Fill data
                    $order->fill($orderChangedData);
    
                    // Save the order
                    $order->save();
    
                    Log::info('Order saved successfully', ['id_order_bp' => $order->id_order_bp]);

                }  else {

                    Log::info('Order exists and is not updated: ', ['id_order_bp' => $order->id_order_bp]);
                }

                // Format date fields if they exist
                $this->formatDateFields($vehicleSubset, ['purchase_date']);

                // Retrieve existing vehicle based on vin
                $vehicle = Vehicle::firstOrNew(['vin' => $vehicleSubset['vin']]);

                // Compare and update only changed fields
                $vehicleChangedData = array_diff_assoc($vehicleSubset, $vehicle->toArray());
                
                // Remove null values and empty strings from the changed data
                $vehicleChangedData = array_filter($vehicleChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($vehicleChangedData)) {
                    
                    // Fill data
                    $vehicle->fill($vehicleChangedData);

                    // Save the vehicle
                    $vehicle->save();

                    Log::info('Vehicle saved successfully', ['vin' => $vehicle->vin]);

                } else {

                    Log::info('Vehicle exists and is not updated: ', ['vin' => $vehicle->vin]);
                }

                // Link vehicle to order
                if (!$order->vehicle()->where('id', $vehicle->id)->exists()) {

                    $vehicle->orders()->save($order);

                    Log::info('Order linked to vehicle successfully', [
                        'order_id' => $order->id,
                        'vehicle_id' => $vehicle->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to vehicle and is not updated', [
                        'order_id' => $order->id,
                        'vehicle_id' => $vehicle->id,
                    ]);
                }

                // Retrieve existing customer based on id_client_bp - Client segment
                $client = Customer::firstOrNew(['id_client_bp' => $clientSubset['id_client_bp'], 'type' => 'Client']);
                
                // Compare and update only changed fields
                $clientChangedData = array_diff_assoc($clientSubset, $client->toArray());

                // Remove null values and empty strings from the changed data
                $clientChangedData = array_filter($clientChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($clientChangedData)) {

                    // Fill data
                    $client->fill($clientChangedData);

                    // Save the customer - Client
                    $client->save();

                    Log::info('Customer saved successfully', ['id_client_bp' => $client->id_client_bp]);
                    
                } else {

                    Log::info('Customer exists and is not updated', ['id_client_bp' => $client->id_client_bp]);

                }

                // Link client to order
                if (!$order->customer()->where('id', $client->id)->exists()) {

                    $client->orders()->save($order);

                    Log::info('Order linked to client successfully', [
                        'order_id' => $order->id,
                        'client_id' => $client->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to client and is not updated', [
                        'order_id' => $order->id,
                        'client_id' => $client->id,
                    ]);
                }
                // End Client segment


                // Retrieve existing sales executive based on id_sales_executive_bp
                $salesExecutive = SalesExecutive::firstOrNew(['id_sales_executive_bp' => $salesExecutiveSubset['id_sales_executive_bp']]);

                // Compare and update only changed fields
                $salesExecutiveChangedData = array_diff_assoc($salesExecutiveSubset, $salesExecutive->toArray());

                // Remove null values and empty strings from the changed data
                $salesExecutiveChangedData = array_filter($salesExecutiveChangedData, function ($value) {
                    return $value !== null && $value !== '';
                });

                // Update only if there are non-null and non-empty string changes
                if (!empty($salesExecutiveChangedData)) {
                    $salesExecutive->fill($salesExecutiveChangedData);

                    // Save the sales executive
                    $salesExecutive->save();

                    Log::info('Sales executive saved successfully', ['id_sales_executive_bp' => $salesExecutive->id_sales_executive_bp]);

                } else {

                    Log::info('Sales executive exists and is not updated', ['id_sales_executive_bp' => $salesExecutive->id_sales_executive_bp]);

                }

                // Link sales executive to order
                if (!$order->salesExecutive()->where('id', $salesExecutive->id)->exists()) {

                    $salesExecutive->orders()->save($order);

                    Log::info('Order linked to sales executive successfully', [
                        'order_id' => $order->id,
                        'sales_executive_id' => $salesExecutive->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to sales executive and is not updated', [
                        'order_id' => $order->id,
                        'sales_executive_id' => $salesExecutive->id,
                    ]);
                }

                // End sales executive segment


                // Retrieve existing branch based on name or create a new one
                $branch = Branch::firstOrNew(['name' => $request->branch]);
                
                $branch->save();
                    
                Log::info('Branch saved successfully', ['branch_id' => $branch->id]);


                // Link branch to order
                if (!$order->branch()->where('id', $branch->id)->exists()) {

                    $branch->orders()->save($order);

                    Log::info('Order linked to branch successfully', [
                        'order_id' => $order->id,
                        'branch_id' => $branch->id,
                    ]);

                } else {
                    
                    Log::info('Order already linked to branch and is not updated', [
                        'order_id' => $order->id,
                        'branch_id' => $branch->id,
                    ]);
                }

                // End branch segment

                //Link vehicle with client
                // Check if the relationship already exists
                if (!$client->vehicles->contains($vehicle->id)) {
                    $client->vehicles()->attach($vehicle->id);
        
                    Log::info('Customer linked to vehicle successfully', ['client_id' => $client->id, 'vehicle_id' => $vehicle->id]);

                } else {

                    Log::info('Customer is already linked to this vehicle', ['client_id' => $client->id, 'vehicle_id' => $vehicle->id]);

                }

                // End Link Vehicle with client segment
    
                DB::commit();
            
            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Failed to save order: ',['error_message' => $e->getMessage()]);
    
                return response()->json([
                    'status' => 'error',
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'An error occurred during processing order'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        }

        return response()->json([
            'status' => 'success',
            'code' => Response::HTTP_OK,
            'message' => 'Invoices stored successfully',
            'totalInvoices' => $request->total,
            'totalValid' => count($validatedInvoices['valid']),
            'valid_invoices' => $validOrderIds,
            'totalInvalid' => count($validatedInvoices['invalid']),
            'invalid_invoices' => $validatedInvoices['invalid']
        ], Response::HTTP_OK);
    }


    private function formatDateFields(&$data, $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = date('Y-m-d H:i:s', strtotime($data[$field]));
            }
        }
    }

    private function formatNumberFields(&$data, $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                // Assuming you want to format numbers as float with 2 decimal places
                $data[$field] = (string)((float)$data[$field]);
            }
        }
    }

    private function renameKeysAndIntersect($array, $keys, $prefixToRemove, $prefixToUse) {
        // Intersect keys with the provided array
        $subset = array_intersect_key($array, array_flip($keys));
    
        // Find keys with the prefix and rename them
        foreach ($subset as $oldKey => $value) {
            // Check if the key contains the prefix to remove
            if (strpos($oldKey, $prefixToRemove) !== false) {
                // Replace the prefix with the new prefix
                $newKey = str_replace($prefixToRemove, $prefixToUse, $oldKey);
                // Assign the value to the new key and unset the old key
                $subset[$newKey] = $value;
                unset($subset[$oldKey]);
            }
        }
    
        return $subset;
    }
}
