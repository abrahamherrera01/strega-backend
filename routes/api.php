<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\CustomerController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SalesExecutiveController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardLeadsCarteraController;
use App\Http\Controllers\DashboardServiciosController;
use App\Http\Controllers\DashboardVentasController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/authenticable', [AuthController::class, 'authenticable'])->name('authenticable');

// Ingresar rutas que quieras proteger
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/getUser', [AuthController::class, 'getUser']);
});

// Routes for customers
Route::resource('/customer', CustomerController::class, [
    'only' => ['index','store','update','destroy']
]);

Route::post('/customers/link-customer-to-vehicle', [CustomerController::class, 'linkCustomerToVehicle']);
Route::post('/customers/getCustomersByWord', [CustomerController::class, 'getCustomersByWord']);
Route::get('/customers/getVehiclesWithOrdersByCustomer/{customer_id}', [CustomerController::class, 'getVehiclesWithOrdersByCustomer']);
Route::post('/customers/uploadImageCustomer', [CustomerController::class, 'uploadImageCustomer']);

Route::get('/getImage/{filename}', [CustomerController::class, 'getImage']);




// Routes for vehicles
Route::resource('/vehicle', VehicleController::class, [
    'only' => ['index','store','update','destroy']
]);



// Routes for orders
Route::resource('/order', OrderController::class, [
    'only' => ['index','store','update','destroy']
]);

Route::post('/orders/link-to-vehicle', [OrderController::class, 'linkToVehicle']);

Route::post('/orders/link-to-customer', [OrderController::class, 'linkToCustomer']);

Route::post('/orders/link-to-biller', [OrderController::class, 'linkToBiller']);

Route::post('/orders/link-to-contact', [OrderController::class, 'linkToContact']);

Route::post('/orders/link-to-legal', [OrderController::class, 'linkToLegal']);

Route::post('/orders/link-to-sales-executive', [OrderController::class, 'linkToSalesExecutive']);

Route::get('/orders/{id_order_bp}', [OrderController::class, 'getByIdOrderBp']);

Route::post('orders/link-to-branch', [OrderController::class, 'linkToBranch']);



// Routes for salesExecutives
Route::resource('/salesExecutive', SalesExecutiveController::class, [
    'only' => ['index','store','update','destroy']
]);


// Routes for branches
Route::resource('/branch', BranchController::class, [
    'only' => ['index','store','update','destroy']
]);

// Routes for load Surveys - Sales and Aftersales
Route::post('/loadSurveysAfterSale', [App\Http\Controllers\SurveyController::class, 'loadSurveysAfterSale']);
Route::post('/loadSurveysSale', [App\Http\Controllers\SurveyController::class, 'loadSurveysSale']);


// Routes for store invoices
Route::post('/invoices/storeAftersaleInvoices', [InvoicesController::class, 'storeAftersaleInvoices']);
Route::post('/invoices/storeSaleInvoices', [InvoicesController::class, 'storeSaleInvoices']);
Route::post('/invoices/storePreownedInvoices', [InvoicesController::class, 'storePreownedInvoices']);


// Routes for temporal leads and cartera
Route::post('/storeLeadsTemp', [DashboardLeadsCarteraController::class, 'storeLeadsTemp']);
Route::post('/storeCarteraTemp', [DashboardLeadsCarteraController::class, 'storeCarteraTemp']);

Route::get('/getLeadsIncidencesMetrics', [DashboardLeadsCarteraController::class, 'getLeadsIncidencesMetrics']);
Route::get('/getCarteraIncidencesMetrics', [DashboardLeadsCarteraController::class, 'getCarteraIncidencesMetrics']);

Route::get('/getSourceLeadMetrics', [DashboardLeadsCarteraController::class, 'getSourceLeadMetrics']);
Route::get('/getAssignedLeadsByExecutiveMetrics', [DashboardLeadsCarteraController::class, 'getAssignedLeadsByExecutiveMetrics']);
Route::get('/getAssignedCarteraBySourceAndDepartment', [DashboardLeadsCarteraController::class, 'getAssignedCarteraBySourceAndDepartment']);
Route::get('/getAssignedCarteraBySourceAndExecutive', [DashboardLeadsCarteraController::class, 'getAssignedCarteraBySourceAndExecutive']);
Route::get('/getIncommingCarteraSurveyedUntraceableByDepartment', [DashboardLeadsCarteraController::class, 'getIncommingCarteraSurveyedUntraceableByDepartment']);
Route::get('/getCarteraUntraceableByExecutive', [DashboardLeadsCarteraController::class, 'getCarteraUntraceableByExecutive']);
Route::get('/getInconsistenciesByDepartment', [DashboardLeadsCarteraController::class, 'getInconsistenciesByDepartment']);
Route::get('/getInconsistenciesByExecutive', [DashboardLeadsCarteraController::class, 'getInconsistenciesByExecutive']);
Route::get('/getComplainsByDepartment', [DashboardLeadsCarteraController::class, 'getComplainsByDepartment']);
Route::get('/getComplainsByExecutive', [DashboardLeadsCarteraController::class, 'getComplainsByExecutive']);
Route::get('/getDetailByExecutive', [DashboardLeadsCarteraController::class, 'getDetailByExecutive']);


// Routes for temporal servicio
Route::post('/storeServiciosTemp', [DashboardServiciosController::class, 'storeServiciosTemp']);

Route::get('getCsiNpsSummaryAndIncidents', [DashboardServiciosController::class, 'getCsiNpsSummaryAndIncidents']);

// Comparativas trimestrales
Route::get('quarterlyComparisons', [DashboardServiciosController::class, 'quarterlyComparisons']);
// Comparativas NPS
Route::get('npsComparisons', [DashboardServiciosController::class, 'npsComparisons']);

// Ilocalizables por tipo y ejecutivo
Route::get('untraceableTypeExecutive', [DashboardServiciosController::class, 'untraceableTypeExecutive']);

// Cliente con quejas por tipo y área
Route::get('customerComplaintsByTypeAreaWorkshop', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaWorkshop']);
Route::get('customerComplaintsByTypeAreaAdviser', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaAdviser']);
Route::get('customerComplaintsByTypeAreaWarranty', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaWarranty']);
Route::get('customerComplaintsByTypeAreaDeliveries', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaDeliveries']);
Route::get('customerComplaintsByTypeAreaWashed', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaWashed']);
Route::get('customerComplaintsByTypeAreaGeneralService', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaGeneralService']);
Route::get('customerComplaintsByTypeAreaCash', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaCash']);
Route::get('customerComplaintsByTypeAreaAppointments', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaAppointments']);
Route::get('customerComplaintsByTypeAreaSales', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaSales']);
Route::get('customerComplaintsByTypeAreaProduct', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaProduct']);
Route::get('customerComplaintsByTypeAreaReplacementParts', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaReplacementParts']);
Route::get('customerComplaintsByTypeAreaReception', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaReception']);
Route::get('customerComplaintsByTypeAreaFacilities', [DashboardServiciosController::class, 'customerComplaintsByTypeAreaFacilities']);

Route::get('trackingWorkshopComplaints', [DashboardServiciosController::class, 'trackingWorkshopComplaints']);

Route::get('complaintsAdvisorPointContact', [DashboardServiciosController::class, 'complaintsAdvisorPointContact']);

// Routes for temporal venta
Route::post('/storeVentasTemp', [DashboardVentasController::class, 'storeVentasTemp']);
Route::get('/getVentasIncidencesMetrics', [DashboardVentasController::class, 'getVentasIncidencesMetrics']);
Route::get('/getNPSbyCurrentQuarter', [DashboardVentasController::class, 'getNPSbyCurrentQuarter']);
Route::get('/getDeliveriesSurveyedAndComplaints', [DashboardVentasController::class, 'getDeliveriesSurveyedAndComplaints']);
Route::get('/getComplaintsbyCurrentQuarter', [DashboardVentasController::class, 'getComplaintsbyCurrentQuarter']);
Route::get('/getUntraceablesByExecutives', [DashboardVentasController::class, 'getUntraceablesByExecutives']);
Route::get('/getIncidencesByDepartment', [DashboardVentasController::class, 'getIncidencesByDepartment']);
