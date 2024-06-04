<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderStoreRequest;
use App\Http\Requests\OrderUpdateRequest;

use App\Models\Order;
use App\Models\Vehicle;
use App\Models\Customer;
use App\Models\SalesExecutive;
use App\Models\Branch;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        try {
            // Retrieve a paginated list of orders
            $orders = Order::paginate(10);

            Log::info('Orders retrieved successfully', ['count' => $orders->count()]);

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $orders,
            ];

        } catch (\Exception $e) {

            Log::error('Failed to retrieve orders: ', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during processing retrieving list.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function store(OrderStoreRequest $request)
    {
        try {
        
            DB::beginTransaction();

            // Validated request data
            $validatedData = $request->validated();

            // Format date fields if they exist
            $this->formatDateFields($validatedData, ['service_date', 'service_billing_date', 'sale_billing_date']);

            // Retrieve existing order based on id_order_bp
            $order = Order::firstOrNew(['id_order_bp' => $validatedData['id_order_bp']]);

            // Compare and update only changed fields
            $changedData = array_diff_assoc($validatedData, $order->toArray());

            // Remove null values and empty strings from the changed data
            $changedData = array_filter($changedData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Update only if there are non-null and non-empty string changes
            if (!empty($changedData)) {
                $order->fill($changedData);

                // Save the order
                $order->save();

                Log::info('Order saved successfully', ['id_order_bp' => $order->id_order_bp]);

            }

            DB::commit();
            
            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $order,
            ];
        
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to save order: ',['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => 'An error occurred during processing order',
            ];
        }

        return response()->json($data, $data["code"]);
    }

    public function update(OrderUpdateRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            // Validate request data
            $validatedData = $request->validated();

            // Format date fields if they exist
            $this->formatDateFields($validatedData, ['service_date', 'service_billing_date', 'sale_billing_date']);

            // Retrieve existing order based on ID
            $order = Order::findOrFail($id);

            // Compare and update only changed fields
            $changedData = array_diff_assoc($validatedData, $order->toArray());

            // Remove null values and empty strings from the changed data
            $changedData = array_filter($changedData, function ($value) {
                return $value !== null && $value !== '';
            });

            // Update only if there are non-null and non-empty string changes
            if (!empty($changedData)) {
                $order->fill($changedData);

                // Save the order
                $order->save();

                // Log success message
                Log::info('Order updated successfully', ['id' => $order->id]);
            }

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'data'   => $order,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update order', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during updating order.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Retrieve existing order based on ID
            $order = Order::findOrFail($id);

            Log::info('Order details before deletion', ['order' => $order]);

            // Delete the order
            $order->delete();

            Log::info('Order deleted successfully', ['order_id' => $id]);

            DB::commit();

            $data = [
                'code'   => 200,
                'status' => 'success',
                'message' => 'Order deleted successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete order', ['error_message' => $e->getMessage()]);

            $data = [
                'status' => 'error',
                'code'   => 500,
                'errors' => ['An error occurred during deleting order.'],
            ];
        }

        return response()->json($data, $data["code"]);
    }

    public function getByIdOrderBp($idOrderBp)
    {
        try {
            // Retrieve the order by id_order_bp
            $order = Order::where('id_order_bp', $idOrderBp)->first();

            if ($order) {

                Log::info('Order found', ['id_order_bp' => $idOrderBp]);

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => $order,
                ];
            } else {

                Log::error('Order not found', ['id_order_bp' => $idOrderBp]);

                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Order not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('Failed to retrieve order', ['error_message' => $e->getMessage()]);

            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred at retrieving order by id.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function linkToVehicle(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        try {
            $order = Order::find($request->input('order_id'));
            $vehicle = Vehicle::find($request->input('vehicle_id'));

            if ($order && $vehicle) {
                // Link the order to the vehicle
                $vehicle->orders()->save($order);

                Log::info('Order linked to vehicle successfully', [
                    'order_id' => $order->id,
                    'vehicle_id' => $vehicle->id,
                ]);
                
                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => [
                        'message' => 'Order linked to vehicle successfully',
                    ],
                ];
            } else {

                Log::error('Order or vehicle not found', [
                    'order_id' => $request->input('order_id'),
                    'vehicle_id' => $request->input('vehicle_id'),
                ]);

                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Order or vehicle not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('Failed to link order to vehicle', ['error_message' => $e->getMessage()]);

            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred at linking order to vehicle.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function linkToCustomer(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        try {
            $order = Order::find($request->input('order_id'));
            $customer = Customer::find($request->input('customer_id'));

            if ($order && $customer) {
                // Link the order with the customer
                $customer->orders()->save($order);

                Log::info('Order linked to customer successfully', [
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                ]);

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => [
                        'message' => 'Order linked to customer successfully',
                    ],
                ];
            } else {

                Log::error('Order or customer not found', [
                    'order_id' => $request->input('order_id'),
                    'customer_id' => $request->input('customer_id'),
                ]);

                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Order or customer not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('Failed to link order to customer', ['error_message' => $e->getMessage()]);

            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred at linking order to customer'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function linkToBiller(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'customer_id' => 'required|exists:customers,id',
        ]);

        try {
            $order = Order::find($request->input('order_id'));
            $biller = Customer::find($request->input('customer_id'));

            if ($order && $biller) {
                // Link the order with the biller
                $order->biller()->associate($biller);

                $order->save();

                Log::info('Order linked to biller successfully', [
                    'order_id' => $order->id,
                    'biller_id' => $biller->id,
                ]);

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => [
                        'message' => 'Order linked to biller successfully',
                    ],
                ];
            } else {

                Log::error('Order or biller not found', [
                    'order_id' => $request->input('order_id'),
                    'biller_id' => $request->input('customer_id'),
                ]);

                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Order or biller not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('Failed to link order to biller', ['error_message' => $e->getMessage()]);

            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred at linking order to biller.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function linkToContact(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'contact_id' => 'required|exists:customers,id',
        ]);

        try {
            $order = Order::find($request->input('order_id'));
            $contact = Customer::find($request->input('contact_id'));

            if ($order && $contact) {
                // Link the order with the contact
                $order->contact()->associate($contact);

                $order->save();

                Log::info('Order linked to contact successfully', [
                    'order_id' => $order->id,
                    'contact_id' => $contact->id,
                ]);

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => [
                        'message' => 'Order linked to contact successfully',
                    ],
                ];
            } else {

                Log::error('Order or contact not found', [
                    'order_id' => $request->input('order_id'),
                    'contact_id' => $request->input('contact_id'),
                ]);

                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Order or contact not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('Failed to link order to contact', ['error_message' => $e->getMessage()]);

            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred at linking order to contact.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function linkToLegal(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'legal_id' => 'required|exists:customers,id',
        ]);

        try {
            $order = Order::find($request->input('order_id'));
            $legal = Customer::find($request->input('legal_id'));

            if ($order && $legal) {
                // Link the order with the legal
                $order->legal()->associate($legal);

                $order->save();

                Log::info('Order linked to legal successfully', [
                    'order_id' => $order->id,
                    'legal_id' => $legal->id,
                ]);

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => [
                        'message' => 'Order linked to legal successfully',
                    ],
                ];
            } else {

                Log::error('Order or legal not found', [
                    'order_id' => $request->input('order_id'),
                    'legal_id' => $request->input('legal_id'),
                ]);

                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Order or legal not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('Failed to link order to legal', ['error_message' => $e->getMessage()]);

            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred at linking order to legal.'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function linkToSalesExecutive(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'sales_executive_id' => 'required|exists:sales_executives,id',
        ]);

        try {
            $order = Order::find($request->input('order_id'));
            $salesExecutive = SalesExecutive::find($request->input('sales_executive_id'));

            if ($order && $salesExecutive) {
                // Link the order with the sales executive
                $order->salesExecutive()->associate($salesExecutive);
                
                $order->save();

                Log::info('Order linked to sales executive successfully', [
                    'order_id' => $order->id,
                    'sales_executive_id' => $salesExecutive->id,
                ]);

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => [
                        'message' => 'Order linked to sales_executive successfully',
                    ],
                ];
            } else {

                Log::error('Order or sales executive not found', [
                    'order_id' => $request->input('order_id'),
                    'sales_executive_id' => $request->input('sales_executive_id'),
                ]);

                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Order or sales executive not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('Failed to link order to sales executive', ['error_message' => $e->getMessage()]);

            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred at linking order to sales executive'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function linkToBranch(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        try {
            $order = Order::find($request->input('order_id'));
            $branch = Branch::find($request->input('branch_id'));

            if ($order && $branch) {
                // Link the order with the branch
                $order->branch()->associate($branch);
                
                $order->save();

                Log::info('Order linked to branch successfully', [
                    'order_id' => $order->id,
                    'branch_id' => $branch->id,
                ]);

                $data = [
                    'code'   => 200,
                    'status' => 'success',
                    'data'   => [
                        'message' => 'Order linked to branch successfully',
                    ],
                ];
            } else {

                Log::error('Order or branch not found', [
                    'order_id' => $request->input('order_id'),
                    'branch_id' => $request->input('branch_id'),
                ]);

                $data = [
                    'code'   => 404,
                    'status' => 'error',
                    'errors' => ['Order or branch not found'],
                ];
            }
        } catch (\Exception $e) {

            Log::error('Failed to link order to branch', ['error_message' => $e->getMessage()]);

            $data = [
                'code'   => 500,
                'status' => 'error',
                'errors' => ['An error occurred at linking order to branch'],
            ];
        }

        return response()->json($data, $data['code']);
    }

    private function formatDateFields(&$data, $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = date('Y-m-d H:i:s', strtotime($data[$field]));
            }
        }
    }
}
