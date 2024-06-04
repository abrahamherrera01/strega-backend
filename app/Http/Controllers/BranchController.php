<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchStoreRequest;
use App\Http\Requests\BranchUpdateRequest;

use App\Models\Branch;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchController extends Controller
{
    public function index()
    {
        try {
            // Retrieve a paginated list of branches
            $branches = Branch::paginate(10); // You can adjust the number of items per page

            Log::info('Branches retrieved successfully', ['total_branches' => $branches->total()]);

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $branches,
            ];

        } catch (\Exception $e) {

            Log::error('Failed to retrieve branches', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(BranchStoreRequest $request)
    {
        try {
            DB::beginTransaction();

            // Validated request data
            $validatedData = $request->validated();
            
            // Retrieve existing branch based on name or create a new one
            $branch = Branch::firstOrNew(['name' => $validatedData['name']]);
            
            // Compare and update only changed fields
            $changedData = array_diff_assoc($validatedData, $branch->toArray());

            $branch->save();
                
            Log::info('Branch saved successfully', ['branch_id' => $branch->id]);

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $branch,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to save branch', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing branch.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }

    public function update(BranchUpdateRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            // Validate the request data
            $validatedData = $request->validated();
            
            // Retrieve the existing branch by ID
            $branch = Branch::findOrFail($id);
    
            $branch->fill($validatedData);

            // Save the branch
            $branch->save();

            Log::info('Branch updated successfully', ['branch_id' => $branch->id]);
        
            DB::commit();
    
            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $branch,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update branch', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during updating branch.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Retrieve the branch by ID
            $branch = Branch::findOrFail($id);

            Log::info('Deleting branch', ['branch' => $branch]);

            // Delete the branch
            $branch->delete();

            // Log success message
            Log::info('Branch deleted successfully', ['branch_id' => $branch->id]);

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'message' => 'Branch deleted successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete branch', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during deleting branch.'],
            ];
        }

        return response()->json($data, $data['code']);
    }
}
