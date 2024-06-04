<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;

use App\Models\Vehicle;
use App\Models\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class CustomerController extends Controller
{   
    public function index()
    {
        try {
            // Retrieve a paginated list of customers
            $customers = Customer::paginate(10);
    
            Log::info('Customers retrieved successfully', ['count' => $customers->total()]);
            
            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $customers,
            ];
    
        } catch (\Exception $e) {

            Log::error('Failed to retrieve customers', ['error_message' => $e->getMessage()]);
    
            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing retrieving list.'],
            ];
        }
    
        return response()->json($data, $data['code']);
    }

    public function store(CustomerStoreRequest $request)
    {
        try {
        
            DB::beginTransaction();

            // Validated request data
            $validatedData = $request->validated();

            // Retrieve existing customer based on id_client_bp
            $customer = Customer::firstOrNew(['id_client_bp' => $validatedData['id_client_bp'], 'type' => $validatedData['type']]);
            
            // Compare and update only changed fields
            $changedData = array_diff_assoc($validatedData, $customer->toArray());

            // Remove null values and empty strings from the changed data
            $changedData = array_filter($changedData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Update only if there are non-null and non-empty string changes
            if (!empty($changedData)) {
                $customer->fill($changedData);

                // Save the customer
                $customer->save();

                Log::info('Customer saved successfully', ['id_client_bp' => $customer->id_client_bp]);

            } else {

                Log::info('Customer exists and is not updated', ['id_client_bp' => $customer->id_client_bp]);

            }

            DB::commit();
            
            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $customer,
            ];
        
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to save customer', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing customer.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }

    public function update(CustomerUpdateRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            // Validate the request data
            $validatedData = $request->validated();
            
            // Retrieve the existing customer by ID
            $customer = Customer::findOrFail($id);

            // Compare and update only changed fields
            $changedData = array_diff_assoc($validatedData, $customer->toArray());

            // Remove null values and empty strings from the changed data
            $changedData = array_filter($changedData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Update only if there are non-null and non-empty string changes
            if (!empty($changedData)) {
                $customer->fill($changedData);

                // Save the customer
                $customer->save();

                Log::info('Customer updated successfully', ['id_client_bp' => $customer->id_client_bp, 'changed_data' => $changedData]);

            }

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $customer,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update customer', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during updating customer.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Retrieve the customer by ID
            $customer = Customer::findOrFail($id);

            Log::info('Customer details before deletion', ['customer' => $customer]);

            // Delete the customer
            $customer->delete();

            Log::info('Customer deleted successfully', ['id_client_bp' => $customer->id_client_bp]);

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'message' => 'Customer deleted successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete customer', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during deleting customer.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function getByIdCustomer($idCustomer, $type)
    {
        try {
            // Retrieve the customer by id_client_bp and type
            $customer = Customer::where('id_client_bp', $idCustomer)->where('type', $type)->first();

            if ($customer) {

                Log::info('Customer found', ['id_customer' => $idCustomer, 'type' => $type]);

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => $customer,
                ];
            } else {

                Log::error('Customer not found', ['id_customer' => $idCustomer, 'type' => $type]);

                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Customer not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('An error occurred at finding customer', ['error_message' => $e->getMessage()]);

            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred during processing.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function linkCustomerToVehicle(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'vehicle_id'  => 'required|exists:vehicles,id',
        ]);
        
        try {
            $customer = Customer::find($request->input('customer_id'));
            $vehicle = Vehicle::find($request->input('vehicle_id'));
        
            if ($customer && $vehicle) {
                // Check if the relationship already exists
                if (!$customer->vehicles->contains($vehicle->id)) {
                    // Attach the vehicle to the customer
                    $customer->vehicles()->attach($vehicle->id);
        
                    Log::info('Customer linked to vehicle successfully', ['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id]);
        
                    $data = [
                        'code'   => 200,
                        'status' => 'success',
                        'data'   => [
                            'message' => 'Customer linked to vehicle successfully',
                        ],
                    ];
                } else {

                    Log::error('Customer is already linked to this vehicle', ['customer_id' => $customer->id, 'vehicle_id' => $vehicle->id]);
        
                    $data = [
                        'code'   => 400,
                        'status' => 'error',
                        'errors' => ['Customer is already linked to this vehicle'],
                    ];
                }
            } else {

                Log::error('Customer or vehicle not found');
        
                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Customer or vehicle not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('An error occurred at linking vehicle and customer', ['error_message' => $e->getMessage()]);
        
            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred at linking vehicle and customer.'],
            ];
        }
        
        return response()->json($data, $data['code']);
    }

    public function getCustomersByWord(Request $request)
    {
        try {
            $fullName = $request->input('fullName', '');
            $phone = $request->input('phone', '');
            $email = $request->input('email', '');
            $vehicle = $request->input('vehicle', '');

            $allInputsEmpty = empty($fullName) && empty($phone) && empty($email) && empty($vehicle);

            if ($allInputsEmpty) {
                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => [],
                ];
            } else {
                $customers = $this->queryCustomers($fullName, $phone, $email, $vehicle);

                $customers->each(function ($customer) {
                    // Retrieve orders associated with vehicles related to the customer
                    $orders = $customer->vehicleOrders()->get();
                
                
                    // Calculate total gross price
                    $totalGrossPrice = $orders->sum('gross_price');
                
                    // Calculate total tax price
                    $totalTaxPrice = $orders->sum('tax_price');
                
                    // Calculate total price
                    $totalPrice = $orders->sum('total_price');
                
                    // Assign the calculated totals to the customer object
                    $customer->sum_orders = [
                        [
                            'customer_id' => $customer->id,
                            'total_gross_price' => $totalGrossPrice,
                            'total_tax_price' => $totalTaxPrice,
                            'total_total_price' => $totalPrice,
                        ]
                    ];
                });

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => $customers,
                ];

                Log::info('Customers retrieved by keywords', [
                    'fullName' => $fullName,
                    'phone' => $phone,
                    'email' => $email,
                    'vehicle' => $vehicle,
                    'count' => count($customers)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to retrieve customers', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred at retrieving customers.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    private function queryCustomers($fullName, $phone, $email, $vehicle)
    {
        $customers = Customer::query()->where('type', 'Client');

        if (!empty($fullName)) {
            $customers->where('full_name', 'like', "%$fullName%");
        }

        if (!empty($phone)) {
            $customers->where(function ($query) use ($phone) {
                $query->where('phone_1', 'like', "%$phone%")
                    ->orWhere('phone_2', 'like', "%$phone%")
                    ->orWhere('cellphone', 'like', "%$phone%");
            });
        }

        if (!empty($email)) {
            $customers->where('email_1', 'like', "%$email%");
        }

        if (!empty($vehicle)) {
            $customers->whereHas('vehicles', function ($query) use ($vehicle) {
                $query->where('vin', 'like', "%$vehicle%");
            });
        }

        return $customers->paginate(12);
    }

    public function getVehiclesWithOrdersByCustomer(int $customer_id)
    {
        try {
            // Retrieve the customer by ID
            $customer = Customer::find($customer_id);

            if ($customer) {
                // Eager load vehicles with orders
                $vehicles = ($customer->vehiclesWithOrders());
                $saleVehicles = $customer->vehiclesWithSaleOrders();
                $aftersaleVehicles = $customer->vehiclesWithAftersaleOrders();

                Log::info('Vehicles with orders retrieved successfully for customer', ['customer_id' => $customer_id, 'saleCount' => count($saleVehicles), 'aftersaleCount' => count($aftersaleVehicles)]);

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'saleVehicles'   => $saleVehicles,
                    'aftersaleVehicles' => $aftersaleVehicles,
                    'data' => $vehicles
                ];

            } else {
                Log::error('Customer not found', ['customer_id' => $customer_id]);

                $data = [
                    'status' => 'error',
                    'code'   => 404,
                    'errors' => ["The client with id $customer_id does not exist."],
                ];
            }

        } catch (\Exception $e) {
            Log::error('Failed to retrieve vehicles with orders for customer', ['customer_id' => $customer_id, 'error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing retrieving vehicles with orders.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function uploadImageCustomer(Request $request){

        if(is_array($request->all()))
        {
            $rules = [
              'customer_id' => 'required|exists:customers,id',                    
              'path' => 'required|image',                    
            ];
                  
            try{

                $validator = \Validator::make($request->all(), $rules);
    
            if($validator->fails()){

                $data = array(
                    'status' => 'error',
                    'code'   => '200',
                    'errors'  => $validator->errors()->all()
                );
            }else{     

                $customer = Customer::where('id', $request->customer_id)->first();

                $nombre_directorio = 'customers';

                $directorio = storage_path() . '/app/' . $nombre_directorio;
                if (!file_exists($directorio)) {
                    mkdir($directorio, 0777, true);
                }


                if($customer->picture!=null){
                    $ruta_archivo = $directorio.'/'.$customer->picture;
                    Storage::delete($ruta_archivo);
                }
    
                
                $image = $request->file('path');

                if ($request->hasFile('path') ) {

                $nombre_archivo = $image->getClientOriginalName(); 
                $extension = $image->getClientOriginalExtension();
                $nombre_archivo_nevo = $customer->id_client_bp.'_'.time() . '_' . uniqid() . '.' . $extension;

                //se puede mandar un numero del 1 al 10 depende de la calidad que se quiera obtener mientras más
                //bajo el numero menor resolucion y menor peso van a tener la imagenes 
                $imagen=\ImageHelper::uploadImage($request->file('path'),'customers',$nombre_archivo_nevo);  

                //actualizar el campo en la tabla de customers
                $customer->picture= $imagen;
                $customer->save();

                $data = array(
                    'status' => 'success',
                    'code'   => '200',
                    'message' => 'imagen almacenada correctamente'
                );  

                }else{
                $data = array(
                    'status' => 'error',
                    'code'   => '200',
                    'message' => 'no se pudo almacenar correctamente'
                );  
                }
            }
    
            }catch (Exception $e){
            $data = array(
                'status' => 'error',
                'code'   => '200',
                'message' => 'Los datos enviados no son correctos, ' . $e
            );
            }  
      
        }else{
        $data = array(
            'status' => 'error',
            'code'   => '200',
            'message'  => "Complete todos los campos"
        );
        }
            
        return response()->json($data, $data['code']);   
    }

    public function getImage($filename){
        header('Access-Control-Allow-Origin: *');
        $file = '';
        try{
            $file = Storage::disk('customers')->get($filename);
        }catch( \Exception $e ){
            $file = Storage::disk('customers')->get('icon.jpg');
        } 
        return ($file);
    }
}
