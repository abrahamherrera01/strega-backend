<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleStoreRequest;
use App\Http\Requests\VehicleUpdateRequest;

use App\Models\Vehicle;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VehicleController extends Controller
{
    public function index()
    {
        try {
            // Retrieve a paginated list of vehicles
            $vehicles = Vehicle::paginate(10);

            Log::info('Customers retrieved successfully', ['count' => $customers->total()]);

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $vehicles,
            ];

        } catch (\Exception $e) {
            
            Log::error('Failed to retrieve vehicles: ', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing retrieving list.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(VehicleStoreRequest $request)
    {   
        try {
        
            DB::beginTransaction();
            
            // Validated request data
            $validatedData = $request->validated();
            
            // Format the purchase_date if it exists
            if (isset($validatedData['purchase_date'])) {
                $validatedData['purchase_date'] = date('Y-m-d H:i:s', strtotime($validatedData['purchase_date']));
            }
            
            // Retrieve existing vehicle based on vin
            $vehicle = Vehicle::firstOrNew(['vin' => $validatedData['vin']]);

            // Compare and update only changed fields
            $changedData = array_diff_assoc($validatedData, $vehicle->toArray());
            
            // Remove null values and empty strings from the changed data
            $changedData = array_filter($changedData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Update only if there are non-null and non-empty string changes
            if (!empty($changedData)) {
                $vehicle->fill($changedData);

                // Save the vehicle
                $vehicle->save();

                Log::info('Vehicle saved successfully', ['vin' => $vehicle->vin]);
            } else {
                Log::info('Vehicle exists and is not updated: ', ['vin' => $vehicle->vin]);
            }

            DB::commit();
            
            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $vehicle,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to save vehicle', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing vehicle.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }

    public function update(VehicleUpdateRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            // Validated request data
            $validatedData = $request->validated();

            // Format the purchase_date if it exists
            if (isset($validatedData['purchase_date'])) {
                $validatedData['purchase_date'] = date('Y-m-d H:i:s', strtotime($validatedData['purchase_date']));
            }

            // Retrieve existing vehicle by ID
            $vehicle = Vehicle::findOrFail($id);

            // Compare and update only changed fields
            $changedData = array_diff_assoc($validatedData, $vehicle->toArray());

            // Remove null values and empty strings from the changed data
            $changedData = array_filter($changedData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Update only if there are non-null and non-empty string changes
            if (!empty($changedData)) {
                $vehicle->fill($changedData);

                // Save the updated vehicle
                $vehicle->save();

                Log::info('Vehicle updated successfully', ['vin' => $vehicle->vin]);

            } else {

                Log::info('Vehicle is not updated: ', ['vin' => $vehicle->vin]);
            }

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $vehicle,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update vehicle', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during updating vehicle.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Retrieve existing vehicle by id
            $vehicle = Vehicle::findOrFail($id);

            Log::info('Vehicle details before deletion', ['vehicle' => $vehicle]);

            // Delete the vehicle
            $vehicle->delete();

            Log::info('Vehicle deleted successfully', ['vehicle_id' => $id]);

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'message' => 'Vehicle deleted successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete vehicle', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during deleting vehicle.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }
}
