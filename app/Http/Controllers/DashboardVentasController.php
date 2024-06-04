<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CreateVentaTempImport;
use Illuminate\Support\Facades\DB;

use App\Models\VentaTemp;

class DashboardVentasController extends Controller
{

    public function storeVentasTemp( Request $request ){
        $file = $request->file('file');

        Excel::import(new CreateVentaTempImport, $file);

        $response = Session::get('response');

        $data = array(
            'code' => 200,
            'status' => 'success',
            'respuesta' => $response
        );

        return response()->json($data, $data['code']);
    }

    public function getVentasIncidencesMetrics(){

        $departments = VentaTemp::where('mes', 'Marzo')->whereNotNull('sucursal')->distinct()->pluck('sucursal');

        $allVentasByDepartment = VentaTemp::select('sucursal',
                                    DB::raw('COUNT(*) as total_ordenes'))
                                    ->where('mes', 'Marzo')
                                    ->groupBy('sucursal')
                                    ->get();

        $totalVentas = $allVentasByDepartment->sum('total_ordenes');

        $ventasContactedByDepartment = VentaTemp::select('sucursal', 
                                DB::raw('COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END) as total_contacted'),
                                DB::raw('ROUND((COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END) / COUNT(*)) * 100, 2) as porcentaje_contactado'))
                                ->where('mes', 'Marzo')
                                ->groupBy('sucursal')
                                ->get();
        
        $totalContacted = $ventasContactedByDepartment->sum('total_contacted');


        $ventasPromotorsByDepartment = VentaTemp::select('sucursal', 
                                DB::raw('COUNT(CASE WHEN nps in (10) THEN 1 END) as total_promotors'),
                                DB::raw('ROUND((COUNT(CASE WHEN nps in (10) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_promotors'))
                                ->where('mes', 'Marzo')
                                ->groupBy('sucursal')
                                ->get();

        $totalPromotors = $ventasPromotorsByDepartment->sum('total_promotors');



        $ventasNeutralsByDepartment = VentaTemp::select('sucursal', 
                                DB::raw('COUNT(CASE WHEN nps in (9,8,7) THEN 1 END) as total_neutrals'),
                                DB::raw('ROUND((COUNT(CASE WHEN nps in (9,8,7) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_neutrals'))
                                ->where('mes', 'Marzo')
                                ->groupBy('sucursal')
                                ->get();

        $totalNeutrals = $ventasNeutralsByDepartment->sum('total_neutrals');


        $ventasDetractorsByDepartment = VentaTemp::select('sucursal', 
                                DB::raw('COUNT(CASE WHEN nps in (6,5,4,3,2,1) THEN 1 END) as total_detractors'),
                                DB::raw('ROUND((COUNT(CASE WHEN nps in (6,5,4,3,2,1) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_detractors'))
                                ->where('mes', 'Marzo')
                                ->groupBy('sucursal')
                                ->get();

        $totalDetractors = $ventasDetractorsByDepartment->sum('total_detractors');


        $NPS = [];
        foreach ($ventasPromotorsByDepartment as $promotor) {
            $sucursal = $promotor->sucursal;
            $porcentajePromotors = $promotor->porcentaje_promotors;
            $porcentajeDetractors = $ventasDetractorsByDepartment->firstWhere('sucursal', $sucursal)->porcentaje_detractors;
            $NPS[$sucursal] = $porcentajePromotors - $porcentajeDetractors;
        }

        $categories_incidences = VentaTemp::where('mes', 'Marzo')->whereNotNull('incidencia')->distinct()->pluck('incidencia')->toArray();

        $series_incidences = VentaTemp::where('mes', 'Marzo')
            ->selectRaw('incidencia, sucursal, COUNT(*) AS total_incidences')
            ->whereNotNull('incidencia')
            ->groupBy('incidencia', 'sucursal')
            ->get();

        $seriesFormat_incidences = [];

        foreach ($departments as $department) {
            $seriesFormat_incidences[$department]['name'] = $department;
            $seriesFormat_incidences[$department]['data'] = array_fill(0, count($categories_incidences), 0);
        }

        $total_incidences = 0;

        foreach ($series_incidences as $item) {
            $department = $item->sucursal;
            $categoryIndex = array_search($item->incidencia, $categories_incidences);

            $seriesFormat_incidences[$department]['data'][$categoryIndex] = $item->total_incidences;
            $total_incidences += $item->total_incidences;
        }

        $categories_no_contacted = VentaTemp::where('mes', 'Marzo')->whereNotNull('motivo_no_contacto')->whereIn('motivo_no_contacto', ['Cuelga llamada', 'Enlaza no contesta'])->distinct()->pluck('motivo_no_contacto')->toArray();

        $series_no_contacted = VentaTemp::where('mes', 'Marzo')
            ->selectRaw('motivo_no_contacto, sucursal, COUNT(*) AS total_incidences')
            ->whereNotNull('motivo_no_contacto')
            ->whereIn('motivo_no_contacto', ['Cuelga llamada', 'Enlaza no contesta'])
            ->groupBy('motivo_no_contacto', 'sucursal')
            ->get();

        $seriesFormat_no_contacted = [];

        foreach ($departments as $department) {
            $seriesFormat_no_contacted[$department]['name'] = $department;
            $seriesFormat_no_contacted[$department]['data'] = array_fill(0, count($categories_no_contacted), 0);
        }
        
        $total_no_contacted = 0;

        foreach ($series_no_contacted as $item) {
            $department = $item->sucursal;
            $categoryIndex = array_search($item->motivo_no_contacto, $categories_no_contacted);

            $seriesFormat_no_contacted[$department]['data'][$categoryIndex] = $item->total_incidences;
            $total_no_contacted += $item->total_incidences;
        }

        $categories_untraceable = VentaTemp::where('mes', 'Marzo')
            ->whereNotNull('motivo_no_contacto')
            ->whereIn('motivo_no_contacto', ['Numero no disponible', 'Numero equivocado', 'Numero no existe', 'Buzon directo'])
            ->distinct()->pluck('motivo_no_contacto')->toArray();

        $series_untraceable = VentaTemp::where('mes', 'Marzo')
            ->selectRaw('motivo_no_contacto, sucursal, COUNT(*) AS total_incidences')
            ->whereNotNull('motivo_no_contacto')
            ->whereIn('motivo_no_contacto', ['Numero no disponible', 'Numero equivocado', 'Numero no existe', 'Buzon directo'])
            ->groupBy('motivo_no_contacto', 'sucursal')
            ->get();

        $seriesFormat_untraceable = [];

        foreach ($departments as $department) {
            $seriesFormat_untraceable[$department]['name'] = $department;
            $seriesFormat_untraceable[$department]['data'] = array_fill(0, count($categories_untraceable), 0);
        }
        
        $total_untraceable = 0;

        foreach ($series_untraceable as $item) {
            $department = $item->sucursal;
            $categoryIndex = array_search($item->motivo_no_contacto, $categories_untraceable);

            $seriesFormat_untraceable[$department]['data'][$categoryIndex] = $item->total_incidences;
            $total_untraceable += $item->total_incidences;
        }


        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'totalVentas' => $totalVentas,
                'allVentasByDepartment' => $allVentasByDepartment,
                'totalContacted' => $totalContacted,
                'ventasContactedByDepartment' => $ventasContactedByDepartment,
                'totalPromotors' => $totalPromotors,
                'ventasPromotorsByDepartment' => $ventasPromotorsByDepartment,
                'totalNeutrals' => $totalNeutrals,
                'ventasNeutralsByDepartment' => $ventasNeutralsByDepartment,
                'totalDetractors' => $totalDetractors,
                'ventasDetractorsByDepartment' => $ventasDetractorsByDepartment,
                'NPS' => $NPS,
                'departments' => $departments,
                'categories_incidences' => $categories_incidences,
                'series_incidences' => array_values($seriesFormat_incidences),
                'total_incidences' => $total_incidences,
                'categories_no_contacted' => $categories_no_contacted,
                'series_no_contacted' => array_values($seriesFormat_no_contacted),
                'total_no_contacted' => $total_no_contacted,
                'categories_untraceable' => $categories_untraceable,
                'seriesFormat_untraceable' => array_values($seriesFormat_untraceable),
                'totalUntraceable' => $total_untraceable
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getNPSbyCurrentQuarter(){


        $ventasPromotorsByDepartment_january = VentaTemp::select('sucursal', 
                                // DB::raw('ROUND((COUNT(CASE WHEN nps in (10) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_promotors'),
                                // DB::raw('ROUND((COUNT(CASE WHEN nps in (6,5,4,3,2,1) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_detractors'),
                                DB::raw('ROUND((COUNT(CASE WHEN nps in (10) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) - ROUND((COUNT(CASE WHEN nps in (6,5,4,3,2,1) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as NPS_score'))
                                ->where('mes', 'Enero')
                                ->groupBy('sucursal')
                                ->get();

        $ventasPromotorsByDepartment_february = VentaTemp::select('sucursal', 
                                // DB::raw('ROUND((COUNT(CASE WHEN nps in (10) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_promotors'),
                                // DB::raw('ROUND((COUNT(CASE WHEN nps in (6,5,4,3,2,1) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_detractors'),
                                DB::raw('ROUND((COUNT(CASE WHEN nps in (10) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) - ROUND((COUNT(CASE WHEN nps in (6,5,4,3,2,1) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as NPS_score'))
                                ->where('mes', 'Febrero')
                                ->groupBy('sucursal')
                                ->get();

        $ventasPromotorsByDepartment_march = VentaTemp::select('sucursal', 
                                // DB::raw('ROUND((COUNT(CASE WHEN nps in (10) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_promotors'),
                                // DB::raw('ROUND((COUNT(CASE WHEN nps in (6,5,4,3,2,1) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_detractors'),
                                DB::raw('ROUND((COUNT(CASE WHEN nps in (10) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) - ROUND((COUNT(CASE WHEN nps in (6,5,4,3,2,1) THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as NPS_score'))
                                ->where('mes', 'Marzo')
                                ->groupBy('sucursal')
                                ->get();

        $departments = VentaTemp::where('mes', 'Marzo')->whereNotNull('sucursal')->distinct()->pluck('sucursal');

        $seriesFormat_NPS = [];

        $categories_months = ['Enero', 'Febrero', 'Marzo'];

        foreach ($departments as $department) {
            $seriesFormat_NPS[$department]['data'] = array_fill(0, count($categories_months) +1, 0);;
        }
        
        $total_incidences = 0;

        foreach ($departments as $department) {
            
            $january = $ventasPromotorsByDepartment_january->firstWhere('sucursal', $department);
            $february = $ventasPromotorsByDepartment_february->firstWhere('sucursal', $department);
            $march = $ventasPromotorsByDepartment_march->firstWhere('sucursal', $department);


            $seriesFormat_NPS[$department]['data'][0] = $department;
            $seriesFormat_NPS[$department]['data'][1] = $january !=  null ? (float) $january->NPS_score: 0;
            $seriesFormat_NPS[$department]['data'][2] = $february !=  null ? (float) $february->NPS_score: 0;
            $seriesFormat_NPS[$department]['data'][3] = $march !=  null ? (float) $march->NPS_score: 0;
        }


        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $categories_months,
                'seriesFormat_NPS' => array_values($seriesFormat_NPS),
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getUntraceablesByExecutives(){

        $ventasWrong = VentaTemp::where('mes', 'Marzo')
            ->where('motivo_no_contacto', '# Equivocado')
            ->selectRaw('ejecutivo, COUNT(*) as total_ventas_wrong')
            ->groupBy('ejecutivo')
            ->get();

        $ventasUnavailable = VentaTemp::where('mes', 'Marzo')
            ->where('motivo_no_contacto', '# No disponible')
            ->selectRaw('ejecutivo, COUNT(*) as total_ventas_unavailable')
            ->groupBy('ejecutivo')
            ->get();

        $ventasNon_existent = VentaTemp::where('mes', 'Marzo')
            ->where('motivo_no_contacto', '# No existe')
            ->selectRaw('ejecutivo, COUNT(*) as total_ventas_non_existent')
            ->groupBy('ejecutivo')
            ->get();

        $ventasVoicemail = VentaTemp::where('mes', 'Marzo')
            ->where('motivo_no_contacto', 'Buzon directo')
            ->selectRaw('ejecutivo, COUNT(*) as total_ventas_voicemail')
            ->groupBy('ejecutivo')
            ->get();

        $executives = VentaTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();

        $ventasExecutivesWrong = [];
        
        foreach ($executives as $executive) {
            $ventasExecutivesWrong[$executive] = 0;
        }

        foreach ($ventasWrong as $venta) {
            $ejecutivo = $venta->ejecutivo;
            $totalVentas = $venta->total_ventas_wrong;
        
            $index = array_search($ejecutivo, $executives);
        
            $ventasExecutivesWrong[$ejecutivo] += $totalVentas;
        }

        $ventasExecutivesWrong = array_values($ventasExecutivesWrong);

        $ventasExecutivesUnavailable = [];
        
        foreach ($executives as $executive) {
            $ventasExecutivesUnavailable[$executive] = 0;
        }

        foreach ($ventasUnavailable as $venta) {
            $ejecutivo = $venta->ejecutivo;
            $totalVentas = $venta->total_ventas_unavailable;
        
            $index = array_search($ejecutivo, $executives);
        
            $ventasExecutivesUnavailable[$ejecutivo] += $totalVentas;
        }

        $ventasExecutivesUnavailable = array_values($ventasExecutivesUnavailable);


        $ventasExecutivesNon_existent = [];
        
        foreach ($executives as $executive) {
            $ventasExecutivesNon_existent[$executive] = 0;
        }

        foreach ($ventasNon_existent as $venta) {
            $ejecutivo = $venta->ejecutivo;
            $totalVentas = $venta->total_ventas_non_existent;
        
            $index = array_search($ejecutivo, $executives);
        
            $ventasExecutivesNon_existent[$ejecutivo] += $totalVentas;
        }

        $ventasExecutivesNon_existent = array_values($ventasExecutivesNon_existent);


        $ventasExecutivesVoicemail = [];
        
        foreach ($executives as $executive) {
            $ventasExecutivesVoicemail[$executive] = 0;
        }

        foreach ($ventasVoicemail as $venta) {
            $ejecutivo = $venta->ejecutivo;
            $totalVentas = $venta->total_ventas_voicemail;
        
            $index = array_search($ejecutivo, $executives);
        
            $ventasExecutivesVoicemail[$ejecutivo] += $totalVentas;
        }

        $ventasExecutivesVoicemail = array_values($ventasExecutivesVoicemail);


        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $executives,
                'ventasExecutivesWrong' => $ventasExecutivesWrong,
                'ventasExecutivesUnavailable' => $ventasExecutivesUnavailable,
                'ventasExecutivesNon_existent' => $ventasExecutivesNon_existent,
                'ventasExecutivesVoicemail' => $ventasExecutivesVoicemail,
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getDeliveriesSurveyedAndComplaints(){


        $departments = VentaTemp::where('mes', 'Marzo')->whereNotNull('sucursal')->distinct()->pluck('sucursal');

        $allVentasByDepartment = VentaTemp::select('sucursal',
                                    DB::raw('COUNT(*) as total_ordenes'))
                                    ->where('mes', 'Marzo')
                                    ->groupBy('sucursal')
                                    ->get();

        $totalVentas = $allVentasByDepartment->sum('total_ordenes');

        $ventasContactedByDepartment = VentaTemp::select('sucursal', 
                                DB::raw('COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END) as total_contacted'),
                                DB::raw('ROUND((COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END) / COUNT(*)) * 100, 2) as porcentaje_contactado'))
                                ->where('mes', 'Marzo')
                                ->groupBy('sucursal')
                                ->get();
        
        $totalContacted = $ventasContactedByDepartment->sum('total_contacted');


        $ventasComplaintsByDepartment = VentaTemp::select('sucursal', 
                                DB::raw('COUNT(CASE WHEN incidencia = "Queja" THEN 1 END) as total_complaints'),
                                DB::raw('ROUND((COUNT(CASE WHEN incidencia = "Queja" THEN 1 END) / COUNT(CASE WHEN estatus = "CONTACTADO" THEN 1 END)) * 100, 2) as porcentaje_complaints'))
                                ->where('mes', 'Marzo')
                                ->groupBy('sucursal')
                                ->get();
        
        $totalComplaints = $ventasComplaintsByDepartment->sum('total_complaints');

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'departments' => $departments,
                'allVentasByDepartment' => $allVentasByDepartment,
                'totalVentas' => $totalVentas,
                'ventasContactedByDepartment' => $ventasContactedByDepartment,
                'totalContacted' => $totalContacted,
                'ventasComplaintsByDepartment' => $ventasComplaintsByDepartment,
                'totalComplaints' => $totalComplaints
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getComplaintsByCurrentQuarter(){


        $ventasComplaintsByDepartment_january = VentaTemp::select('sucursal', 
                                DB::raw('COUNT(CASE WHEN incidencia = "Queja" THEN 1 END) as total_complaints'))
                                ->where('mes', 'Enero')
                                ->groupBy('sucursal')
                                ->get();

        $ventasComplaintsByDepartment_february = VentaTemp::select('sucursal', 
                                DB::raw('COUNT(CASE WHEN incidencia = "Queja" THEN 1 END) as total_complaints'))
                                ->where('mes', 'Febrero')
                                ->groupBy('sucursal')
                                ->get();


        $ventasComplaintsByDepartment_march = VentaTemp::select('sucursal', 
                                DB::raw('COUNT(CASE WHEN incidencia = "Queja" THEN 1 END) as total_complaints'))
                                ->where('mes', 'Marzo')
                                ->groupBy('sucursal')
                                ->get();

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'ventasComplaintsByDepartment_january' => $ventasComplaintsByDepartment_january,
                'ventasComplaintsByDepartment_february' => $ventasComplaintsByDepartment_february,
                'ventasComplaintsByDepartment_march' => $ventasComplaintsByDepartment_march
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getComplaintsByExecutives(){

        $ventasDelivery = VentaTemp::where('mes', 'Marzo')
            ->where('tipo_queja', 'No cumplio fecha de entrega')
            ->selectRaw('ejecutivo, COUNT(*) as total_ventas_delivery')
            ->groupBy('ejecutivo')
            ->get();

        $ventasNoFollowUp = VentaTemp::where('mes', 'Marzo')
            ->where('tipo_queja', 'No recibio seguimiento de ejecutivo')
            ->selectRaw('ejecutivo, COUNT(*) as total_ventas_seguimiento')
            ->groupBy('ejecutivo')
            ->get();

        $ventasDamages = VentaTemp::where('mes', 'Marzo')
            ->where('tipo_queja', 'Recibio vehiculo con daños o fallas')
            ->selectRaw('ejecutivo, COUNT(*) as total_ventas_damages')
            ->groupBy('ejecutivo')
            ->get();

        $ventasBadAttention = VentaTemp::where('mes', 'Marzo')
            ->where('tipo_queja', 'Mala atencion de ejecutivo')
            ->selectRaw('ejecutivo, COUNT(*) as total_ventas_attention')
            ->groupBy('ejecutivo')
            ->get();

        $executives = VentaTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();

        $ventasExecutivesDelivery = [];
        
        foreach ($executives as $executive) {
            $ventasExecutivesDelivery[$executive] = 0;
        }

        foreach ($ventasDelivery as $venta) {
            $ejecutivo = $venta->ejecutivo;
            $totalVentas = $venta->total_ventas_delivery;
        
            $index = array_search($ejecutivo, $executives);
        
            $ventasExecutivesDelivery[$ejecutivo] += $totalVentas;
        }

        $ventasExecutivesDelivery = array_values($ventasExecutivesDelivery);


        $ventasExecutivesNoFollowUp = [];
        
        foreach ($executives as $executive) {
            $ventasExecutivesNoFollowUp[$executive] = 0;
        }

        foreach ($ventasNoFollowUp as $venta) {
            $ejecutivo = $venta->ejecutivo;
            $totalVentas = $venta->total_ventas_seguimiento;
        
            $index = array_search($ejecutivo, $executives);
        
            $ventasExecutivesNoFollowUp[$ejecutivo] += $totalVentas;
        }

        $ventasExecutivesNoFollowUp = array_values($ventasExecutivesNoFollowUp);


        $ventasExecutivesDamages = [];
        
        foreach ($executives as $executive) {
            $ventasExecutivesDamages[$executive] = 0;
        }

        foreach ($ventasDamages as $venta) {
            $ejecutivo = $venta->ejecutivo;
            $totalVentas = $venta->total_ventas_damages;
        
            $index = array_search($ejecutivo, $executives);
        
            $ventasExecutivesDamages[$ejecutivo] += $totalVentas;
        }

        $ventasExecutivesDamages = array_values($ventasExecutivesDamages);


        $ventasExecutivesBadAttention= [];
        
        foreach ($executives as $executive) {
            $ventasExecutivesBadAttention[$executive] = 0;
        }

        foreach ($ventasBadAttention as $venta) {
            $ejecutivo = $venta->ejecutivo;
            $totalVentas = $venta->total_ventas_attention;
        
            $index = array_search($ejecutivo, $executives);
        
            $ventasExecutivesBadAttention[$ejecutivo] += $totalVentas;
        }

        $ventasExecutivesBadAttention = array_values($ventasExecutivesBadAttention);


        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $executives,
                'ventasExecutivesDelivery' => $ventasExecutivesDelivery,
                'ventasExecutivesNoFollowUp' => $ventasExecutivesNoFollowUp,
                'ventasExecutivesDamages' => $ventasExecutivesDamages,
                'ventasExecutivesBadAttention' => $ventasExecutivesBadAttention,
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getIncidencesByDepartment(){

        $departments = VentaTemp::where('mes', 'Marzo')->whereNotNull('sucursal')->distinct()->pluck('sucursal');

        $categories_incidences= VentaTemp::where('mes', 'Marzo')
            ->whereNotNull('solicitud')
            ->distinct()->pluck('solicitud')->toArray();

        $series_incidences = VentaTemp::where('mes', 'Marzo')
            ->selectRaw('solicitud, sucursal, COUNT(*) AS total_incidences')
            ->whereNotNull('solicitud')
            ->groupBy('solicitud', 'sucursal')
            ->get();

        $seriesFormat_incidences = [];

        foreach ($departments as $department) {
            $seriesFormat_incidences[$department]['name'] = $department;
            $seriesFormat_incidences[$department]['data'] = array_fill(0, count($categories_incidences), 0);
        }
        
        $total_incidences = 0;

        foreach ($series_incidences as $item) {
            $department = $item->sucursal;
            $categoryIndex = array_search($item->solicitud, $categories_incidences);

            $seriesFormat_incidences[$department]['data'][$categoryIndex] = $item->total_incidences;
            $total_incidences += $item->total_incidences;
        }



        $categories_comments= VentaTemp::where('mes', 'Marzo')
            ->whereNotNull('comentario')
            ->distinct()->pluck('comentario')->toArray();

        $series_comments = VentaTemp::where('mes', 'Marzo')
            ->selectRaw('comentario, sucursal, COUNT(*) AS total_incidences')
            ->whereNotNull('comentario')
            ->groupBy('comentario', 'sucursal')
            ->get();

        $seriesFormat_comments = [];

        foreach ($departments as $department) {
            $seriesFormat_comments[$department]['name'] = $department;
            $seriesFormat_comments[$department]['data'] = array_fill(0, count($categories_comments), 0);
        }
        
        $total_incidences = 0;

        foreach ($series_incidences as $item) {
            $department = $item->sucursal;
            $categoryIndex = array_search($item->comentario, $categories_comments);

            $seriesFormat_comments[$department]['data'][$categoryIndex] = $item->total_incidences;
            $total_incidences += $item->total_incidences;
        }


        $categories_complaints= VentaTemp::where('mes', 'Marzo')
            ->whereNotNull('tipo_queja')
            ->distinct()->pluck('tipo_queja')->toArray();

        $series_complaints = VentaTemp::where('mes', 'Marzo')
            ->selectRaw('tipo_queja, sucursal, COUNT(*) AS total_incidences')
            ->whereNotNull('tipo_queja')
            ->groupBy('tipo_queja', 'sucursal')
            ->get();

        $seriesFormat_complaints = [];

        foreach ($departments as $department) {
            $seriesFormat_complaints[$department]['name'] = $department;
            $seriesFormat_complaints[$department]['data'] = array_fill(0, count($categories_complaints), 0);
        }
        
        $total_incidences = 0;

        foreach ($series_complaints as $item) {
            $department = $item->sucursal;
            $categoryIndex = array_search($item->tipo_queja, $categories_complaints);

            $seriesFormat_complaints[$department]['data'][$categoryIndex] = $item->total_incidences;
            $total_incidences += $item->total_incidences;
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories_incidences' => $categories_incidences,
                'seriesFormat_incidences' => array_values($seriesFormat_incidences),
                'categories_comments' => $categories_comments,
                'seriesFormat_comments' => array($seriesFormat_comments),
                'categories_complaints' => $categories_complaints,
                'seriesFormat_complaints' => array_values($seriesFormat_complaints),
            ],
        ];

        return response()->json($response, $response['code']);

    }

}
