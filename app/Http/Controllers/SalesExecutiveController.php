<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesExecutiveStoreRequest;
use App\Http\Requests\SalesExecutiveUpdateRequest;

use App\Models\SalesExecutive;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesExecutiveController extends Controller
{
    public function index()
    {
        try {
            // Retrieve a paginated list of sales executives
            $salesexecutives = SalesExecutive::paginate(10);

            Log::info('Sales executives retrieved successfully', ['total_executives' => $salesexecutives->total()]);

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $salesexecutives,
            ];
            
        } catch (\Exception $e) {

            Log::error('Failed to retrieve sales executives', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing retrieving list.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(SalesExecutiveStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            // Validated request data
            $validatedData = $request->validated();

            // Retrieve existing sales executive based on id_sales_executive_bp
            $salesExecutive = SalesExecutive::firstOrNew(['id_sales_executive_bp' => $validatedData['id_sales_executive_bp']]);

            // Compare and update only changed fields
            $changedData = array_diff_assoc($validatedData, $salesExecutive->toArray());

            // Remove null values and empty strings from the changed data
            $changedData = array_filter($changedData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Update only if there are non-null and non-empty string changes
            if (!empty($changedData)) {
                $salesExecutive->fill($changedData);

                // Save the sales executive
                $salesExecutive->save();

                Log::info('Sales executive saved successfully', ['id_sales_executive_bp' => $salesExecutive->id_sales_executive_bp]);

            } else {

                Log::info('Sales executive exists and is not updated', ['id_sales_executive_bp' => $salesExecutive->id_sales_executive_bp]);

            }

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $salesExecutive,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to save sales executive', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing sales executive.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }

    public function update(SalesExecutiveUpdateRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            // Validated request data
            $validatedData = $request->validated();

            // Find the sales executive by ID
            $salesExecutive = SalesExecutive::findOrFail($id);

            // Compare and update only changed fields
            $changedData = array_diff_assoc($validatedData, $salesExecutive->toArray());

            // Remove null values and empty strings from the changed data
            $changedData = array_filter($changedData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Update only if there are non-null and non-empty string changes
            if (!empty($changedData)) {
                $salesExecutive->fill($changedData);

                // Save the sales executive
                $salesExecutive->save();

                Log::info('Sales executive updated successfully', ['id_sales_executive_bp' => $salesExecutive->id_sales_executive_bp]);

            }

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $salesExecutive,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update sales executive', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during updating sales executive.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Retrieve existing sales executive based on ID
            $salesExecutive = SalesExecutive::findOrFail($id);

            Log::info('Sales executive details before deletion', ['sales_executive' => $salesExecutive]);

            // Delete the sales executive
            $salesExecutive->delete();

            Log::info('Sales executive deleted successfully', ['sales_executive_id' => $id]);

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => ['message' => 'SalesExecutive deleted successfully'],
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            // Log error message
            Log::error('Failed to delete sales executive', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during deleting sales executive.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }
}
