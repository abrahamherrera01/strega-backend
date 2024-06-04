<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CreateServicioTempImport;

use App\Models\ServicioTemp;

use Illuminate\Support\Facades\DB;

class DashboardServiciosController extends Controller
{

    protected $mes_primero;
    protected $mes_segundo;
    protected $mes_actual;
    public function __construct()
    {
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        $mes_actual_numero = date('n'); // Obtiene el número del mes actual
        $mes_ejemplo = $mes_actual_numero - 2; // Obtiene 3 en $mes_ejemplo
        $this->mes_primero = $meses[$mes_actual_numero - 4];
        $this->mes_segundo = $meses[$mes_actual_numero - 3];
        $this->mes_actual = $meses[$mes_ejemplo]; // Obtiene Marzo en $mes_actual
    }
    public function storeServiciosTemp( Request $request ){
        $file = $request->file('file');

        Excel::import(new CreateServicioTempImport, $file);

        $response = Session::get('response');

        $data = array(
            'code' => 200,
            'status' => 'success',
            'respuesta' => $response
        );
        return response()->json($data, $data['code']);
    }

    public function getCsiNpsSummaryAndIncidents(){

        $mes_actual = $this->mes_actual;

        $ordersService = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where('mes', $mes_actual)
                                        ->groupBy('tipo_orden')
                                        ->get();
        
        $totalOrderService = $ordersService->sum('count'); 

        $surveyedService = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where(function($query) use ($mes_actual) {
                                            $query->where('mes', $mes_actual)
                                                  ->where('estatus', 'Contactado');
                                        })
                                        ->groupBy('tipo_orden')
                                        ->get();

        $totalSurveyedService = $surveyedService->sum('count'); 

        $totalPercentSurvey = ($totalSurveyedService / $totalOrderService) * 100;

        for ($i=0; $i < count($ordersService); $i++) { 
            if ($ordersService[$i]->tipo_orden === 'Públicas') {
               $myPublicOrder = $ordersService[$i];
            }
        }

        for ($i=0; $i < count($surveyedService); $i++) { 
            if ($surveyedService[$i]->tipo_orden === 'Públicas') {
               $myPublicSurvey = $surveyedService[$i];
            }
        }
        
        $percentPublicSurvey = ($myPublicSurvey->count / $myPublicOrder->count) * 100;
        
        for ($i=0; $i < count($ordersService); $i++) { 
            if ($ordersService[$i]->tipo_orden === 'Garantias') {
                $myGarantiesOrder = $ordersService[$i];
            }
        }
        
        for ($i=0; $i < count($surveyedService); $i++) { 
            if ($surveyedService[$i]->tipo_orden === 'Garantias') {
                $myGarantiesSurvey = $surveyedService[$i];
            }
        }
        
        $percentGarantiesSurvey = ($myGarantiesSurvey->count / $myGarantiesOrder->count) * 100;

        // Promotores
        $promotersPublicService = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where(function($query) use ($mes_actual) {
                                            $query->where('mes', $mes_actual)
                                                  ->where('recomendacion', 10);
                                        })
                                        ->groupBy('tipo_orden')
                                        ->get();

        $totalPromotersPublicService = $promotersPublicService->sum('count'); 

        for ($i=0; $i < count($promotersPublicService); $i++) { 
            if ($promotersPublicService[$i]->tipo_orden === 'Públicas') {
                $myPromotersPublicService = $promotersPublicService[$i];
            }
        }
        
        for ($i=0; $i < count($promotersPublicService); $i++) { 
            if ($promotersPublicService[$i]->tipo_orden === 'Garantias') {
                $myPromotersGarantiesService = $promotersPublicService[$i];
            }
        }
        $percentPromotersPublic = ($myPromotersPublicService->count / $myPublicSurvey->count ) * 100;
        $percentPromotersGaranties = ($myPromotersGarantiesService->count / $myGarantiesSurvey->count ) * 100;
        $totalPercentPromoters = ($totalPromotersPublicService / $totalSurveyedService) * 100;

        // Fin Promotores

        // Neutrales
        $neutralPublicService = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where(function($query) use ($mes_actual) {
                                            $query->where('mes', $mes_actual)
                                                  ->whereIn('recomendacion', [9, 8, 7]);
                                        })
                                        ->groupBy('tipo_orden')
                                        ->get();

        $totalNeutralPublicGaranties = $neutralPublicService->sum('count');

        for ($i=0; $i < count($neutralPublicService); $i++) { 
            if ($neutralPublicService[$i]->tipo_orden === 'Públicas') {
                $myNeutralPublicService = $neutralPublicService[$i];
            }
        }
        
        for ($i=0; $i < count($neutralPublicService); $i++) { 
            if ($neutralPublicService[$i]->tipo_orden === 'Garantias') {
                $myNeutralGarantiesService = $neutralPublicService[$i];
            }
        }

        $percentNeutralPublic = ($myNeutralPublicService->count / $myPublicSurvey->count) * 100;
        $percentNeutralGaranties = ($myNeutralGarantiesService->count / $myGarantiesSurvey->count) * 100;
        $totalPercentNeutral = ($totalNeutralPublicGaranties / $totalSurveyedService) * 100;

        // Fin Neutrales

        // Detractores
        $detractorPublicService = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where(function($query) use ($mes_actual) {
                                            $query->where('mes', $mes_actual)
                                                  ->whereIn('recomendacion', [6, 5, 4, 3, 2, 1]);
                                        })
                                        ->groupBy('tipo_orden')
                                        ->get();

        $totalDetractorPublicGaranties = $detractorPublicService->sum('count');

        for ($i=0; $i < count($detractorPublicService); $i++) { 
            if ($detractorPublicService[$i]->tipo_orden === 'Públicas') {
                $myDetractorPublicService = $detractorPublicService[$i];
            }
        }
        
        for ($i=0; $i < count($detractorPublicService); $i++) { 
            if ($detractorPublicService[$i]->tipo_orden === 'Garantias') {
                $myDetractorGarantiesService = $detractorPublicService[$i];
            }
        }

        $percentDetractorPublic = ($myDetractorPublicService->count / $myPublicSurvey->count) * 100;
        $percentDetractorGaranties = ($myDetractorGarantiesService->count / $myGarantiesSurvey->count) * 100;
        $totalPercentDetractor = ($totalDetractorPublicGaranties / $totalSurveyedService) * 100;

        // Fin Detractores

        // NPS
        $npsPublic = ($percentPromotersPublic - $percentDetractorPublic);
        $npsGaranties = ($percentPromotersGaranties - $percentDetractorGaranties);
        $totalNps = ($totalPercentPromoters - $totalPercentDetractor);
        // Fin NPS

        // Resumen Incidencias
        $sources = ServicioTemp::where('mes', $mes_actual)->whereNotNull('incidencia')->distinct()->pluck('incidencia')->toArray();
        $tipoOrdenes = ServicioTemp::where('mes', $mes_actual)->whereNotNull('tipo_orden')->distinct()->pluck('tipo_orden')->toArray();
        
        $incidentsSummary =  ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'), 'incidencia')
                                        ->where(function($query) use ($mes_actual) {
                                            $query->where('mes', $mes_actual)
                                                ->whereIn('incidencia', ['Solicitud de info/contacto', 'Queja', 'Baja calificación', 'Sugerencia', 'Comentario', 'Felicitación']);
                                        })
                                        ->groupBy('tipo_orden', 'incidencia')
                                        ->get();
        $seriesFormat = [];
        foreach ($tipoOrdenes as $tipoOrden) {
            $seriesFormat[$tipoOrden]['name'] = $tipoOrden;
            $seriesFormat[$tipoOrden]['data'] = array_fill(0, count($sources), 0);
        }

        foreach ($incidentsSummary as $item) {
            $tipoOrden = $item->tipo_orden;
            $categoryIndex = array_search($item->incidencia, $sources);
            $seriesFormat[$tipoOrden]['data'][$categoryIndex] = $item->count;
        }
        $totalIncidentsSummary = $incidentsSummary->sum('count');

        // No contactados
        $sourcesReasonNotContacted = ServicioTemp::where('mes', $mes_actual)->whereNotNull('motivo_no_contactado')->whereIn('motivo_no_contactado', ['Enlaza no contesta', 'Cuelga llamada', 'No desea ser encuestado'])->distinct();
        $reasonNotContacted = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'), 'motivo_no_contactado')
                                        ->where(function($query) use ($mes_actual) {
                                            $query->where('mes', $mes_actual)
                                                ->whereIn('motivo_no_contactado', ['Enlaza no contesta', 'Cuelga llamada', 'No desea ser encuestado']);
                                        })
                                        ->groupBy('tipo_orden', 'motivo_no_contactado')
                                        ->get();
        $reasonNoContacted = $sourcesReasonNotContacted->pluck('motivo_no_contactado')->toArray();
        $seriesFormatNoContacted = [];
        foreach ($tipoOrdenes as $tipoOrden) {
            $seriesFormatNoContacted[$tipoOrden]['name'] = $tipoOrden;
            $seriesFormatNoContacted[$tipoOrden]['data'] = array_fill(0, count($reasonNoContacted), 0);
        }
        foreach ($reasonNotContacted as $item) {
            $tipoOrden = $item->tipo_orden;
            $categoryIndex = array_search($item->motivo_no_contactado, $reasonNoContacted);
            $seriesFormatNoContacted[$tipoOrden]['data'][$categoryIndex] = $item->count;
        }
        $totalReasonNotContacted = $reasonNotContacted->sum('count');
        $percentNotContacted = ($totalReasonNotContacted / $totalOrderService) * 100;

        // ilocalizables
        $sourcesUntraceable = ServicioTemp::where('mes', $mes_actual)->whereNotNull('motivo_no_contactado')->whereIn('motivo_no_contactado', ['Buzón directo', 'Número no disponible', 'Número equivocado', 'Número no existe'])->distinct();
        $untraceable = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'), 'motivo_no_contactado')
                                        ->where(function($query) use ($mes_actual) {
                                            $query->where('mes', $mes_actual)
                                                ->whereIn('motivo_no_contactado', ['Buzón directo', 'Número no disponible', 'Número equivocado', 'Número no existe']);
                                        })
                                        ->groupBy('tipo_orden', 'motivo_no_contactado')
                                        ->get();
        $sourceUntraceable = $sourcesUntraceable->pluck('motivo_no_contactado')->toArray();
        $seriesFormatUntraceable = [];
        foreach ($tipoOrdenes as $tipoOrden) {
            $seriesFormatUntraceable[$tipoOrden]['name'] = $tipoOrden;
            $seriesFormatUntraceable[$tipoOrden]['data'] = array_fill(0, count($sourceUntraceable), 0);
        }
        foreach ($untraceable as $item) {
            $tipoOrden = $item->tipo_orden;
            $categoryIndex = array_search($item->motivo_no_contactado, $sourceUntraceable);
            $seriesFormatUntraceable[$tipoOrden]['data'][$categoryIndex] = $item->count;
        }
        $totalUntraceable = $untraceable->sum('count');
        $percentUntraceable = ($totalUntraceable / $totalOrderService) * 100;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'ordersService' => $ordersService,
                'totalOrderService' => $totalOrderService,

                'surveyedService' => $surveyedService,
                'totalSurveyedService' => $totalSurveyedService,
                'percentPublicSurvey' => number_format($percentPublicSurvey) . '%',
                'percentGarantiesSurvey' => number_format($percentGarantiesSurvey) . '%',
                'totalPercentSurvey' => number_format($totalPercentSurvey) . '%',

                'promotersPublicService' => $promotersPublicService,
                'totalPromotersPublicService' => $totalPromotersPublicService,
                'percentPromotersPublic' => number_format($percentPromotersPublic) . '%',
                'percentPromotersGaranties' => number_format($percentPromotersGaranties) . '%',
                'totalPercentPromoters' => number_format($totalPercentPromoters) . '%',

                'neutralPublicService' => $neutralPublicService,
                'totalNeutralPublicGaranties' => $totalNeutralPublicGaranties,
                '$percentNeutralPublic' => number_format($percentNeutralPublic) . '%',
                '$percentNeutralGaranties' => number_format($percentNeutralGaranties) . '%',
                '$totalPercentNeutral' => number_format($totalPercentNeutral) . '%',

                'detractorPublicService' => $detractorPublicService,
                'totalDetractorPublicGaranties' => $totalDetractorPublicGaranties,
                'percentDetractorPublic' => number_format($percentDetractorPublic) . '%',
                'percentDetractorGaranties' => number_format($percentDetractorGaranties) . '%',
                'totalPercentDetractor' => number_format($totalPercentDetractor) . '%',

                'npsPublic' => number_format($npsPublic),
                'npsGaranties' => number_format($npsGaranties),
                'totalNps' => number_format($totalNps),

                'categoryIncidents' => $sources,
                'series' => array_values($seriesFormat),
                'totalIncidentsSummary' => $totalIncidentsSummary,
                
                'reasonNoContacted' => $reasonNoContacted,
                'seriesFormatNoContacted' => array_values($seriesFormatNoContacted),
                'totalReasonNotContacted' => $totalReasonNotContacted,
                'percentNotContacted' => number_format($percentNotContacted) . '%',

                'sourceUntraceable' => $sourceUntraceable,
                'seriesFormatUntraceable' => array_values($seriesFormatUntraceable),

                'totalUntraceable' => $totalUntraceable,
                'percentUntraceable' => number_format($percentUntraceable) . '%'
            ]
        ];

        return response()->json($response, $response['code']);
    }

    public function quarterlyComparisons(){
        $mes_actual = $this->mes_actual;
        $mes_primero = $this->mes_primero;
        $mes_segundo = $this->mes_segundo;

        $series = ['Ordenes', 'Encuestados', 'Clientes con queja', '% encuestados', '% quejas'];

        // ***************************** Mes Actual = Marzo *********************************************
        $ordersServiceCurrentMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where('mes', $mes_actual)
                                        ->groupBy('tipo_orden')
                                        ->get();
        $totalOrderServiceCurrentMonth = $ordersServiceCurrentMonth->sum('count');

        $surveyedServiceCurrentMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where(function($query) use ($mes_actual) {
                                            $query->where('mes', $mes_actual)
                                                  ->where('estatus', 'Contactado');
                                        })
                                        ->groupBy('tipo_orden')
                                        ->get();
        $totalSurveyedServiceCurrentMonth = $surveyedServiceCurrentMonth->sum('count'); 

        $incidentsComplaintCurrentMonth =  ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'), 'incidencia')
                                        ->where(function($query) use ($mes_actual) {
                                            $query->where('mes', $mes_actual)
                                                ->whereIn('incidencia', ['Queja']);
                                        })
                                        ->groupBy('tipo_orden', 'incidencia')
                                        ->get();
        $totalIncidentsComplaintCurrentMonth = $incidentsComplaintCurrentMonth->sum('count');

        $percentCurrentMonth = ($totalSurveyedServiceCurrentMonth / $totalOrderServiceCurrentMonth) * 100;

        $percentComplaintCurrentMonth = ($totalIncidentsComplaintCurrentMonth / $totalSurveyedServiceCurrentMonth) * 100;
        // **********************************************************************************************
        // ***************************** Mes Segundo = Febrero ******************************************
        $ordersServiceSecondMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where('mes', $mes_segundo)
                                        ->groupBy('tipo_orden')
                                        ->get();
        $totalOrderServiceSecondMonth = $ordersServiceSecondMonth->sum('count');

        $surveyedServiceSecondMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where(function($query) use ($mes_segundo) {
                                            $query->where('mes', $mes_segundo)
                                                  ->where('estatus', 'Contactado');
                                        })
                                        ->groupBy('tipo_orden')
                                        ->get();
        $totalSurveyedServiceSecondMonth = $surveyedServiceSecondMonth->sum('count'); 

        $incidentsComplaintSecondMonth =  ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'), 'incidencia')
                                        ->where(function($query) use ($mes_segundo) {
                                            $query->where('mes', $mes_segundo)
                                                ->whereIn('incidencia', ['Queja']);
                                        })
                                        ->groupBy('tipo_orden', 'incidencia')
                                        ->get();
        $totalIncidentsComplaintSecondMonth = $incidentsComplaintSecondMonth->sum('count');

        $percentSecondMonth = ($totalSurveyedServiceSecondMonth / $totalOrderServiceSecondMonth) * 100;

        $percentComplaintSecondMonth = ($totalIncidentsComplaintSecondMonth / $totalSurveyedServiceSecondMonth) * 100;
        // **********************************************************************************************
        // ****************************** Mes Primero = Enero *******************************************
        $ordersServiceFirstMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where('mes', $mes_primero)
                                        ->groupBy('tipo_orden')
                                        ->get();
        $totalOrderServiceFirstMonth = $ordersServiceFirstMonth->sum('count');

        $surveyedServiceFirstMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                        ->where(function($query) use ($mes_primero) {
                                            $query->where('mes', $mes_primero)
                                                  ->where('estatus', 'Contactado');
                                        })
                                        ->groupBy('tipo_orden')
                                        ->get();
        $totalSurveyedServiceFirstMonth = $surveyedServiceFirstMonth->sum('count'); 

        $incidentsComplaintFirstMonth =  ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'), 'incidencia')
                                        ->where(function($query) use ($mes_primero) {
                                            $query->where('mes', $mes_primero)
                                                ->whereIn('incidencia', ['Queja']);
                                        })
                                        ->groupBy('tipo_orden', 'incidencia')
                                        ->get();
        $totalIncidentsComplaintFirstMonth = $incidentsComplaintFirstMonth->sum('count');

        $percentFirstMonth = ($totalSurveyedServiceFirstMonth / $totalOrderServiceFirstMonth) * 100;

        $percentComplaintFirstMonth = ($totalIncidentsComplaintFirstMonth / $totalSurveyedServiceFirstMonth) * 100;
        // **********************************************************************************************

        $clientSurvey = [
            $mes_primero => [
                $series[0] => $totalOrderServiceFirstMonth,
                $series[1] => $totalSurveyedServiceFirstMonth, /** $percentFirstMonth, */ 
                $series[2] => $totalIncidentsComplaintFirstMonth, /** $percentComplaintFirstMonth */
                $series[3] => $percentFirstMonth,
                $series[4] => $percentComplaintFirstMonth
            ],
            $mes_segundo => [
                $series[0] => $totalOrderServiceSecondMonth,
                $series[1] => $totalSurveyedServiceSecondMonth, /**$percentSecondMonth,*/
                $series[2] => $totalIncidentsComplaintSecondMonth, /** $percentComplaintSecondMonth */ 
                $series[3] => $percentSecondMonth,
                $series[4] => $percentComplaintSecondMonth
            ],
            $mes_actual => [
                $series[0] => $totalOrderServiceCurrentMonth,
                $series[1] => $totalSurveyedServiceCurrentMonth, /** $percentCurrentMonth, */ 
                $series[2] => $totalIncidentsComplaintCurrentMonth, /** $percentComplaintCurrentMonth, */
                $series[3] => $percentCurrentMonth,
                $series[4] => $percentComplaintCurrentMonth,
            ] 
        ];

        $monthSources = [$mes_primero, $mes_segundo, $mes_actual];
        
        $seriesFormat = [];
        foreach ($series as $serie) {
            $seriesFormat[$serie]['name'] = $serie;
            $seriesFormat[$serie]['data'] = array_fill(0, count($monthSources), 0);
        }
        foreach ($monthSources as $index => $item) {
            foreach ($series as $serie) {
                $seriesFormat[$serie]['data'][$index] = $clientSurvey[$item][$serie];
            }
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                // 'totalOrderServiceSecondMonth' => $totalOrderServiceSecondMonth,
                // 'totalOrderServiceCurrentMonth' => $totalOrderServiceCurrentMonth,
                // 'totalSurveyedServiceSecondMonth' => $totalSurveyedServiceSecondMonth,
                // 'totalSurveyedServiceCurrentMonth' => $totalSurveyedServiceCurrentMonth,
                // 'totalIncidentsComplaintCurrentMonth' => $totalIncidentsComplaintCurrentMonth,
                // 'percentCurrentMonth' => number_format($percentCurrentMonth) . '%',
                // 'percentComplaintCurrentMonth' => number_format($percentComplaintCurrentMonth) . '%',
                'category' => $monthSources,
                'series' => array_values($seriesFormat),
                'Porcentaje_encuestados_enero' => $percentFirstMonth,
                'Porcentaje_encuestados_febrero' => $percentSecondMonth,
                'Porcentaje_encuestados_actual' => $percentCurrentMonth,
                'Porcentaje_quejas_enero' => $percentComplaintFirstMonth,
                'Porcentaje_quejas_febrero' => $percentComplaintSecondMonth,
                'Porcentaje_quejas_actual' => $percentComplaintCurrentMonth
                // 'clientSurvey' => $clientSurvey
            ]
        ];
        return response()->json($response, $response['code']);
    }

    public function npsComparisons(){
        $mes_actual = $this->mes_actual;
        $mes_primero = $this->mes_primero;
        $mes_segundo = $this->mes_segundo;

        $series = ['Públicas', 'Garantias', 'Objetivo'];

        // ***************************** Mes Actual = Marzo *********************************************
        $surveyedService = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                            ->where(function($query) use ($mes_actual) {
                                                $query->where('mes', $mes_actual)
                                                    ->where('estatus', 'Contactado');
                                            })
                                            ->groupBy('tipo_orden')
                                            ->get();
        for ($i=0; $i < count($surveyedService); $i++) { 
            if ($surveyedService[$i]->tipo_orden === $series[0]) {
                $myPublicSurvey = $surveyedService[$i];
            }
        }
        for ($i=0; $i < count($surveyedService); $i++) { 
            if ($surveyedService[$i]->tipo_orden === $series[1]) {
                $myGarantiesSurvey = $surveyedService[$i];
            }
        }
        // Promotores
        $promotersPublicService = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                                ->where(function($query) use ($mes_actual) {
                                                    $query->where('mes', $mes_actual)
                                                        ->where('recomendacion', 10);
                                                })
                                                ->groupBy('tipo_orden')
                                                ->get();
        for ($i=0; $i < count($promotersPublicService); $i++) { 
            if ($surveyedService[$i]->tipo_orden === $series[0]) { /** <-- $promotersPublicService[$i] */
                $myPromotersPublicService = $promotersPublicService[$i];
            }
        }
        for ($i=0; $i < count($promotersPublicService); $i++) { 
            if ($promotersPublicService[$i]->tipo_orden === 'Garantias') {
                $myPromotersGarantiesService = $promotersPublicService[$i];
            }
        }

        $percentPromotersPublic = ($myPromotersPublicService->count / $myPublicSurvey->count ) * 100;
        $percentPromotersGaranties = ($myPromotersGarantiesService->count / $myGarantiesSurvey->count ) * 100;

        // Detractores
        $detractorPublicService = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                                ->where(function($query) use ($mes_actual) {
                                                    $query->where('mes', $mes_actual)
                                                        ->whereIn('recomendacion', [6, 5, 4, 3, 2, 1]);
                                                })
                                                ->groupBy('tipo_orden')
                                                ->get();
        for ($i=0; $i < count($detractorPublicService); $i++) { 
            if ($detractorPublicService[$i]->tipo_orden === $series[0]) {
                $myDetractorPublicService = $detractorPublicService[$i];
            }
        }
        for ($i=0; $i < count($detractorPublicService); $i++) { 
            if ($detractorPublicService[$i]->tipo_orden === 'Garantias') {
                $myDetractorGarantiesService = $detractorPublicService[$i];
            }
        }
        $percentDetractorPublic = ($myDetractorPublicService->count / $myPublicSurvey->count) * 100;
        $percentDetractorGaranties = ($myDetractorGarantiesService->count / $myGarantiesSurvey->count) * 100;

        // NPS
        $npsPublicCurrentMonth = ($percentPromotersPublic - $percentDetractorPublic);
        $npsGarantiesCurrentMonth = ($percentPromotersGaranties - $percentDetractorGaranties);
        $npsTargetCurrentMonth = 95;
        // **********************************************************************************************
        // ***************************** Mes Segundo = Febrero ******************************************
        $surveyedServiceSecondMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                            ->where(function($query) use ($mes_segundo) {
                                                $query->where('mes', $mes_segundo)
                                                    ->where('estatus', 'Contactado');
                                            })
                                            ->groupBy('tipo_orden')
                                            ->get();
        for ($i=0; $i < count($surveyedServiceSecondMonth); $i++) { 
            if ($surveyedServiceSecondMonth[$i]->tipo_orden === $series[0] ) {
                $myPublicSurveySecondMonth = $surveyedServiceSecondMonth[$i];
            }
        }
        for ($i=0; $i < count($surveyedServiceSecondMonth); $i++) { 
            if ($surveyedServiceSecondMonth[$i]->tipo_orden === $series[1]) {
                $myGarantiesSurveySecondMonth = $surveyedServiceSecondMonth[$i];
            }
        }
        // Promotores
        $promotersPublicServiceSecondMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                                ->where(function($query) use ($mes_segundo) {
                                                    $query->where('mes', $mes_segundo)
                                                        ->where('recomendacion', 10);
                                                })
                                                ->groupBy('tipo_orden')
                                                ->get();
                                                
        for ($i=0; $i < count($promotersPublicServiceSecondMonth); $i++) { 
            if ($surveyedServiceSecondMonth[$i]->tipo_orden === $series[0]) {
                $myPublicServiceSecondMonth = $promotersPublicServiceSecondMonth[$i];
            }
        }
        for ($i=0; $i < count($promotersPublicServiceSecondMonth); $i++) { 
            if ($promotersPublicServiceSecondMonth[$i]->tipo_orden === 'Garantias') {
                $myPromotersGarantiesServiceSecondMonth = $promotersPublicServiceSecondMonth[$i];
            }
        }

        $percentPromotersPublicSecondMonth = ($myPublicServiceSecondMonth->count / $myPublicSurveySecondMonth->count ) * 100;
        $percentPromotersGarantiesSecondMonth = ($myPromotersGarantiesServiceSecondMonth->count / $myGarantiesSurveySecondMonth->count ) * 100;

        // Detractores
        $detractorPublicServiceSecondMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                                ->where(function($query) use ($mes_segundo) {
                                                    $query->where('mes', $mes_segundo)
                                                        ->whereIn('recomendacion', [6, 5, 4, 3, 2, 1]);
                                                })
                                                ->groupBy('tipo_orden')
                                                ->get();
        for ($i=0; $i < count($detractorPublicServiceSecondMonth); $i++) { 
            if ($detractorPublicServiceSecondMonth[$i]->tipo_orden === $series[0]) {
                $myDetractorPublicServiceSecondMonth = $detractorPublicServiceSecondMonth[$i];
            }
        }
        for ($i=0; $i < count($detractorPublicServiceSecondMonth); $i++) { 
            if ($detractorPublicServiceSecondMonth[$i]->tipo_orden === 'Garantias') {
                $myDetractorGarantiesServiceSecondMonth = $detractorPublicServiceSecondMonth[$i];
            }
        }
        $percentDetractorPublicSecondMonth = ($myDetractorPublicServiceSecondMonth->count / $myPublicSurveySecondMonth->count) * 100;
        $percentDetractorGarantiesSecondMonth = ($myDetractorGarantiesServiceSecondMonth->count / $myGarantiesSurveySecondMonth->count) * 100;

        // NPS
        $npsPublicSecondMonth = ($percentPromotersPublicSecondMonth - $percentDetractorPublicSecondMonth);
        $npsGarantiesSecondMonth = ($percentPromotersGarantiesSecondMonth - $percentDetractorGarantiesSecondMonth);
        $npsTargetSecondMonth = 95;
        // ********************************************************************************************
        // ***************************** Mes Primero = Enero ******************************************
        $surveyedServiceFirstMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                            ->where(function($query) use ($mes_primero) {
                                                $query->where('mes', $mes_primero)
                                                    ->where('estatus', 'Contactado');
                                            })
                                            ->groupBy('tipo_orden')
                                            ->get();
        for ($i=0; $i < count($surveyedServiceFirstMonth); $i++) { 
            if ($surveyedServiceFirstMonth[$i]->tipo_orden === $series[0] ) {
                $myPublicSurveyFirstMonth = $surveyedServiceFirstMonth[$i];
            }
        }
        for ($i=0; $i < count($surveyedServiceFirstMonth); $i++) { 
            if ($surveyedServiceFirstMonth[$i]->tipo_orden === $series[1]) {
                $myGarantiesSurveyFirstMonth = $surveyedServiceFirstMonth[$i];
            }
        }
        // Promotores
        $promotersPublicServiceFirstMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                                ->where(function($query) use ($mes_primero) {
                                                    $query->where('mes', $mes_primero)
                                                        ->where('recomendacion', 10);
                                                })
                                                ->groupBy('tipo_orden')
                                                ->get();
                                                
        for ($i=0; $i < count($promotersPublicServiceFirstMonth); $i++) { 
            if ($surveyedServiceFirstMonth[$i]->tipo_orden === $series[0]) {
                $myPublicServiceFirstMonth = $promotersPublicServiceFirstMonth[$i];
            }
        }
        for ($i=0; $i < count($promotersPublicServiceFirstMonth); $i++) { 
            if ($promotersPublicServiceFirstMonth[$i]->tipo_orden === 'Garantias') {
                $myPromotersGarantiesServiceFirstMonth = $promotersPublicServiceFirstMonth[$i];
            }
        }

        $percentPromotersPublicFirstMonth = ($myPublicServiceFirstMonth->count / $myPublicSurveyFirstMonth->count ) * 100;
        $percentPromotersGarantiesFirstMonth = ($myPromotersGarantiesServiceFirstMonth->count / $myGarantiesSurveyFirstMonth->count ) * 100;

        // Detractores
        $detractorPublicServiceFirstMonth = ServicioTemp::select('tipo_orden', \DB::raw('COUNT(tipo_orden) as count'))
                                                ->where(function($query) use ($mes_primero) {
                                                    $query->where('mes', $mes_primero)
                                                        ->whereIn('recomendacion', [6, 5, 4, 3, 2, 1]);
                                                })
                                                ->groupBy('tipo_orden')
                                                ->get();
        for ($i=0; $i < count($detractorPublicServiceFirstMonth); $i++) { 
            if ($detractorPublicServiceFirstMonth[$i]->tipo_orden === $series[0]) {
                $myDetractorPublicServiceFirstMonth = $detractorPublicServiceFirstMonth[$i];
            }
        }
        for ($i=0; $i < count($detractorPublicServiceFirstMonth); $i++) { 
            if ($detractorPublicServiceFirstMonth[$i]->tipo_orden === 'Garantias') {
                $myDetractorGarantiesServiceFirstMonth = $detractorPublicServiceFirstMonth[$i];
            }
        }

        $percentDetractorPublicFirstMonth = ($myDetractorPublicServiceFirstMonth->count / $myPublicSurveyFirstMonth->count) * 100;
        $percentDetractorGarantiesFirstMonth = ($myDetractorGarantiesServiceFirstMonth->count / $myGarantiesSurveyFirstMonth->count) * 100;

        // NPS
        $npsPublicFirstMonth = ($percentPromotersPublicFirstMonth - $percentDetractorPublicFirstMonth);
        $npsGarantiesFirstMonth = ($percentPromotersGarantiesFirstMonth - $percentDetractorGarantiesFirstMonth);
        $npsTargetFirstMonth = 95;
        // ********************************************************************************************

        $clientSurvey = [
            $mes_primero => [
                $series[0] => $npsPublicFirstMonth,
                $series[1] => $npsGarantiesFirstMonth,
                $series[2] => $npsTargetFirstMonth
            ],
            $mes_segundo => [
                $series[0] => $npsPublicSecondMonth,
                $series[1] => $npsGarantiesSecondMonth,
                $series[2] => $npsTargetSecondMonth
            ],
            $mes_actual => [
                $series[0] => $npsPublicCurrentMonth,
                $series[1] => $npsGarantiesCurrentMonth,
                $series[2] => $npsTargetCurrentMonth
            ] 
        ];

        $monthSources = [$mes_primero, $mes_segundo, $mes_actual];

        $seriesFormat = [];
        foreach ($series as $serie) {
            $seriesFormat[$serie]['name'] = $serie;
            $seriesFormat[$serie]['data'] = array_fill(0, count($monthSources), 0);
        }
        foreach ($monthSources as $index => $item) {
            foreach ($series as $serie) {
                $seriesFormat[$serie]['data'][$index] = $clientSurvey[$item][$serie];
            }
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'category' => $monthSources,
                'series' => array_values($seriesFormat),
            ]
        ];

        return response()->json($response, $response['code']);
    
    }

    public function untraceableTypeExecutive(){
        $mes_actual = $this->mes_actual;

        // Búsqueda de asesores
        $executives = ServicioTemp::distinct()->pluck('asesor');

        // Motivo no contactado
        $reasonNotContacted = ServicioTemp::where('mes', $mes_actual)
                                            ->whereNotNull('motivo_no_contactado')
                                            ->whereIn('motivo_no_contactado', ['Buzón directo', 'Número equivocado', 'Número no existe'])->distinct();
        $reasonNoContacted = $reasonNotContacted->pluck('motivo_no_contactado')->toArray();

        // Cuenta ilocalizables por asesor
        $untraceableByAdvisor = ServicioTemp::select('asesor', 'motivo_no_contactado', \DB::raw('COUNT(motivo_no_contactado) as count'))
                                            ->where('mes', $mes_actual)
                                            ->whereIn('asesor', [$executives[0], $executives[1], $executives[2], $executives[3], $executives[4], $executives[5], $executives[7]])
                                            ->whereIn('motivo_no_contactado', [$reasonNoContacted[0], $reasonNoContacted[1], $reasonNoContacted[2]])
                                            ->groupBy('asesor', 'motivo_no_contactado')
                                            ->get();

        $seriesFormat = [];
        foreach ($reasonNoContacted as $reason) {
            $seriesFormat[] = [
                'name' => $reason,
                'data' => array_fill(0, count($executives), 0)
            ];
        }

        $executivesArray = $executives->toArray();

        foreach ($executivesArray as $key => $executive) {
            foreach ($untraceableByAdvisor as $untraceable) {
                if ($untraceable->asesor === $executive) {
                    $executiveIndex = $key;
                    $reasonIndex = array_search($untraceable->motivo_no_contactado, array_column($seriesFormat, 'name'));
                    if ($reasonIndex !== false) {
                        $seriesFormat[$reasonIndex]['data'][$executiveIndex] = $untraceable->count;
                    }
                }
            }
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'executives' => $executives,
                'reasonNoContacted' => $reasonNoContacted,
                'seriesFormat' => $seriesFormat
            ]
        ];
    
        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaWorkshop(){
        $mes_actual = $this->mes_actual;

        // Taller
        // Nueva falla después de la reparación
        $workshopNewFailAfterRep = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Nueva falla después de la reparación", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Nueva falla después de la reparación", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Nueva falla después de la reparación" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $workshopNewFailAfterReparation = $workshopNewFailAfterRep->first()->total_general;

        // Daño generado en servicio
        $damageGeneratedServ = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Daño generado en servicio", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Daño generado en servicio", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Daño generado en servicio" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $damageGeneratedService = $damageGeneratedServ->first()->total_general;

        // Falla persistente
        $persistentFail = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Falla persistente", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Falla persistente", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Falla persistente" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $persistentFailure = $persistentFail->first()->total_general;

        // Fallo en diagnóstico
        $diagnosticFail = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Fallo en diagnostico", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Fallo en diagnostico", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Fallo en diagnostico" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $diagnosticFailure = $diagnosticFail->first()->total_general;

        // Demora en diagnostico
        $delayDiag = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Demora en diagnostico", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Demora en diagnostico", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Demora en diagnostico" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $delayDiagnosis = $delayDiag->first()->total_general;

        // Demora en reparación/mantenimiento
        $delayRepairMaintenan = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Demora en reparación/mantenimiento", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Demora en reparación/mantenimiento", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Demora en reparación/mantenimiento" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $delayRepairMaintenance = $delayRepairMaintenan->first()->total_general;

        $totalWorkshop = $workshopNewFailAfterReparation + $damageGeneratedService + $persistentFailure + $diagnosticFailure + $delayDiagnosis + $delayRepairMaintenance;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'workshopNewFailAfterReparation' => $workshopNewFailAfterReparation,
                'damageGeneratedService' => $damageGeneratedService,
                'damageGeneratedService' => $damageGeneratedService,
                'persistentFailure' => $persistentFailure,
                'diagnosticFailure' => $diagnosticFailure,
                'delayDiagnosis' => $delayDiagnosis,
                'delayRepairMaintenance' => $delayRepairMaintenance,
                'totalWorkshop' => $totalWorkshop
            ]
        ];
    
        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaAdviser(){
        $mes_actual = $this->mes_actual;
        
        // Asesor
        // Falta de credibilidad
        $adviserLackCredi = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "Falta de credibilidad", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "Falta de credibilidad", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "Falta de credibilidad" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserLackCredibility = $adviserLackCredi->first()->total_general;

        // Realizaron reparaciones no autorizadas
        $adviserUnauthorizedRepair = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "Realizaron reparaciones no autorizadas", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "Realizaron reparaciones no autorizadas", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "Realizaron reparaciones no autorizadas" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserUnauthorizedRepairs = $adviserUnauthorizedRepair->first()->total_general;

        // Mala atención del asesor
        $adviserBadAttent = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "Mala atención del asesor", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "Mala atención del asesor", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "Mala atención del asesor" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserBadAttention = $adviserBadAttent->first()->total_general;

        // No ofreció movilidad
        $adviserDidNotOfferMobil = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "No ofreció movilidad", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "No ofreció movilidad", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "No ofreció movilidad" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserDidNotOfferMobility = $adviserDidNotOfferMobil->first()->total_general;

        // No realizaron servicios acordados
        $adviserTheyDidNotPerformAgreedService = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "No realizaron servicios acordados", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "No realizaron servicios acordados", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "No realizaron servicios acordados" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserTheyDidNotPerformAgreedServices = $adviserTheyDidNotPerformAgreedService->first()->total_general;

        // Error en las cotizaciones
        $adviserErrorInQuote = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "Error en las cotizaciones", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "Error en las cotizaciones", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "Error en las cotizaciones" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserErrorInQuotes = $adviserErrorInQuote->first()->total_general;

        // No recibió seguimiento del asesor
        $adviserDidNotReceiveF = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "No recibió seguimiento del asesor", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "No recibió seguimiento del asesor", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "No recibió seguimiento del asesor" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserDidNotReceiveFollow = $adviserDidNotReceiveF->first()->total_general;

        // No envió Cotización/Poliza/ Contrato BSI
        $adviserDidNotSendBSIquotePolicyC = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "No envió Cotización/Poliza/ Contrato BSI", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "No envió Cotización/Poliza/ Contrato BSI", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "No envió Cotización/Poliza/ Contrato BSI" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserDidNotSendBSIquotePolicyContract = $adviserDidNotSendBSIquotePolicyC->first()->total_general;

        // No entrego obsequios prometidos
        $adviserDoNotDeliverPromisedGift = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "No entrego obsequios prometidos", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "No entrego obsequios prometidos", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "No entrego obsequios prometidos" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserDoNotDeliverPromisedGifts = $adviserDoNotDeliverPromisedGift->first()->total_general;

        // Precio diferente al cotizado
        $adviserPriceDifferentThanQuot = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "Precio diferente al cotizado", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "Precio diferente al cotizado", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "Precio diferente al cotizado" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserPriceDifferentThanQuoted = $adviserPriceDifferentThanQuot->first()->total_general;

        // Mal registro de datos del cliente
        $adviserBadRegistrationCustomerDat = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "Mal registro de datos del cliente", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "Mal registro de datos del cliente", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "Mal registro de datos del cliente" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserBadRegistrationCustomerData = $adviserBadRegistrationCustomerDat->first()->total_general;

        // Mal servicio del asesor
        $adviserBadServ = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "Mal servicio del asesor", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "Mal servicio del asesor", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "Mal servicio del asesor" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserBadService = $adviserBadServ->first()->total_general;

        // No sellaron póliza
        $adviserDidNotSealPoli = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "No sellaron póliza", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "No sellaron póliza", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "No sellaron póliza" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserDidNotSealPolicy = $adviserDidNotSealPoli->first()->total_general;

        // Faltante de pertenencias
        $adviserLackBelong = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Asesor" AND tipo_queja = "Faltante de pertenencias", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Asesor" AND tipo_queja_2 = "Faltante de pertenencias", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Asesor" OR area_2 = "Asesor")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Asesor" AS area_1, "Faltante de pertenencias" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserLackBelongings = $adviserLackBelong->first()->total_general;

        $totalAdviser = $adviserLackCredibility + $adviserUnauthorizedRepairs + $adviserBadAttention + $adviserDidNotOfferMobility + $adviserTheyDidNotPerformAgreedServices +
                        $adviserErrorInQuotes + $adviserDidNotReceiveFollow + $adviserDidNotSendBSIquotePolicyContract + $adviserDoNotDeliverPromisedGifts + 
                        $adviserPriceDifferentThanQuoted + $adviserBadRegistrationCustomerData + $adviserBadService + $adviserDidNotSealPolicy + $adviserLackBelongings;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'adviserLackCredibility' => $adviserLackCredibility,
                'adviserUnauthorizedRepairs' => $adviserUnauthorizedRepairs,
                'adviserBadAttention' => $adviserBadAttention,
                'adviserDidNotOfferMobility' => $adviserDidNotOfferMobility,
                'adviserTheyDidNotPerformAgreedServices' => $adviserTheyDidNotPerformAgreedServices,
                'adviserErrorInQuotes' => $adviserErrorInQuotes,
                'adviserDidNotReceiveFollow' => $adviserDidNotReceiveFollow,
                'adviserDidNotSendBSIquotePolicyContract' => $adviserDidNotSendBSIquotePolicyContract,
                'adviserDoNotDeliverPromisedGifts' => $adviserDoNotDeliverPromisedGifts,
                'adviserPriceDifferentThanQuoted' => $adviserPriceDifferentThanQuoted,
                'adviserBadRegistrationCustomerData' => $adviserBadRegistrationCustomerData,
                'adviserBadService' => $adviserBadService,
                'adviserDidNotSealPolicy' => $adviserDidNotSealPolicy,
                'adviserLackBelongings' => $adviserLackBelongings,
                '$totalAdviser' => $totalAdviser
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaWarranty(){
        $mes_actual = $this->mes_actual;

        // Warranty
        // No validaron garantía
        $adviserDidNotValidateWarran = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Garantias" AND tipo_queja = "No validaron garantía", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Garantias" AND tipo_queja_2 = "No validaron garantía", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Garantias" OR area_2 = "Garantias")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Garantias" AS area_1, "No validaron garantía" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserDidNotValidateWarranty = $adviserDidNotValidateWarran->first()->total_general;

        // Tiempo de resolución garantías
        $adviserResolutionTimeGuarante = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Garantias" AND tipo_queja = "Tiempo de resolución garantías", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Garantias" AND tipo_queja_2 = "Tiempo de resolución garantías", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Garantias" OR area_2 = "Garantias")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Garantias" AS area_1, "Tiempo de resolución garantías" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $adviserResolutionTimeGuarantees = $adviserResolutionTimeGuarante->first()->total_general;

        $totalGuarantees = $adviserDidNotValidateWarranty + $adviserResolutionTimeGuarantees;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'adviserDidNotValidateWarranty' => $adviserDidNotValidateWarranty,
                'adviserResolutionTimeGuarantees' => $adviserResolutionTimeGuarantees,
                'totalGuarantees' => $totalGuarantees
            ] 
        ];

        return response()->json($response, $response['code']);

    }

    public function customerComplaintsByTypeAreaDeliveries(){
        $mes_actual = $this->mes_actual;

        // Deliveries
        // Retraso en la entrega
        $deliveryD = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Entregas" AND tipo_queja = "Retraso en la entrega", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Entregas" AND tipo_queja_2 = "Retraso en la entrega", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Entregas" OR area_2 = "Entregas")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Entregas" AS area_1, "Retraso en la entrega" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $deliveryDelay = $deliveryD->first()->total_general;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'deliveryDelay' => $deliveryDelay
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaWashed(){
        $mes_actual = $this->mes_actual;

        // Lavado
        // Mal lavado
        $washedB = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Lavado" AND tipo_queja = "Mal lavado", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Lavado" AND tipo_queja_2 = "Mal lavado", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Lavado" OR area_2 = "Lavado")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Lavado" AS area_1, "Mal lavado" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $washedBad = $washedB->first()->total_general;

        // Vehículo no fue lavado
        $washedVehicleWasNotWash = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Lavado" AND tipo_queja = "Vehículo no fue lavado", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Lavado" AND tipo_queja_2 = "Vehículo no fue lavado", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Lavado" OR area_2 = "Lavado")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Lavado" AS area_1, "Vehículo no fue lavado" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $washedVehicleWasNotWashed = $washedVehicleWasNotWash->first()->total_general;

        $totalWashed = $washedBad + $washedVehicleWasNotWashed;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'washedBad' => $washedBad,
                'washedVehicleWasNotWashed' => $washedVehicleWasNotWashed,
                'totalWashed' => $totalWashed,
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaGeneralService(){
        $mes_actual = $this->mes_actual;

        // Servicio General
        // Mala experiencia en general
        $generalServiceExperienceOver = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Servicio General" AND tipo_queja = "Mala experiencia en general", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Servicio General" AND tipo_queja_2 = "Mala experiencia en general", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Servicio General" OR area_2 = "Servicio General")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Servicio General" AS area_1, "Mala experiencia en general" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $generalServiceExperienceOverall = $generalServiceExperienceOver->first()->total_general;

        // Mala atención general
        $generalServiceBadGeneralAttent = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Servicio General" AND tipo_queja = "Mala atención general", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Servicio General" AND tipo_queja_2 = "Mala atención general", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Servicio General" OR area_2 = "Servicio General")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Servicio General" AS area_1, "Mala atención general" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $generalServiceBadGeneralAttention = $generalServiceBadGeneralAttent->first()->total_general;

        // Precio alto
        $generalServiceHighP = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Servicio General" AND tipo_queja = "Precio alto", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Servicio General" AND tipo_queja_2 = "Precio alto", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Servicio General" OR area_2 = "Servicio General")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Servicio General" AS area_1, "Precio alto" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $generalServiceHighPrice = $generalServiceHighP->first()->total_general;

        // Mala atencion del gerente
        $generalServiceBadAttentionFromManger = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Servicio General" AND tipo_queja = "Mala atencion del gerente", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Servicio General" AND tipo_queja_2 = "Mala atencion del gerente", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Servicio General" OR area_2 = "Servicio General")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Servicio General" AS area_1, "Mala atencion del gerente" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $generalServiceBadAttentionFromManager = $generalServiceBadAttentionFromManger->first()->total_general;

        $totalGeneralService = $generalServiceExperienceOverall + $generalServiceBadGeneralAttention + $generalServiceHighPrice + $generalServiceBadAttentionFromManager;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'generalServiceExperienceOverall' => $generalServiceExperienceOverall,
                'generalServiceBadGeneralAttention' => $generalServiceBadGeneralAttention,
                'generalServiceHighPrice' => $generalServiceHighPrice,
                'generalServiceBadAttentionFromManager' => $generalServiceBadAttentionFromManager,
                'totalGeneralService' => $totalGeneralService
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaCash(){
        $mes_actual = $this->mes_actual;

        // Caja
        // Demora en caja
        $cashD = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Caja" AND tipo_queja = "Demora en caja", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Caja" AND tipo_queja_2 = "Demora en caja", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Caja" OR area_2 = "Caja")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Caja" AS area_1, "Demora en caja" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $cashDelay = $cashD->first()->total_general;

        // Error de factura
        $cashInvoiceErr = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Caja" AND tipo_queja = "Error de factura", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Caja" AND tipo_queja_2 = "Error de factura", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Caja" OR area_2 = "Caja")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Caja" AS area_1, "Error de factura" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $cashInvoiceError = $cashInvoiceErr->first()->total_general;

        // Demora en factura
        $cashInvoiceD = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Caja" AND tipo_queja = "Demora en factura", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Caja" AND tipo_queja_2 = "Demora en factura", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Caja" OR area_2 = "Caja")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Caja" AS area_1, "Demora en factura" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $cashInvoiceDelay = $cashInvoiceD->first()->total_general;

        // Mala atención en caja
        $cashBadAttentionPay = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Caja" AND tipo_queja = "Mala atención en caja", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Caja" AND tipo_queja_2 = "Mala atención en caja", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Caja" OR area_2 = "Caja")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Caja" AS area_1, "Mala atención en caja" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $cashBadAttentionPaying = $cashBadAttentionPay->first()->total_general;

        $totalCash = $cashDelay + $cashInvoiceError + $cashInvoiceDelay + $cashBadAttentionPaying;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'cashDelay' => $cashDelay,
                'cashInvoiceError' => $cashInvoiceError,
                'cashInvoiceDelay' => $cashInvoiceDelay,
                'cashBadAttentionPaying' => $cashBadAttentionPaying,
                'totalCash' => $totalCash
            ] 
        ];

        return response()->json($response, $response['code']);
        
    }

    public function customerComplaintsByTypeAreaAppointments(){
        $mes_actual = $this->mes_actual;

        // Citas
        // Numerosas llamadas para agendar cita
        $appointmentsNumerousCallsScheduleAppoint = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Citas" AND tipo_queja = "Numerosas llamadas para agendar cita", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Citas" AND tipo_queja_2 = "Numerosas llamadas para agendar cita", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Citas" OR area_2 = "Citas")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Citas" AS area_1, "Numerosas llamadas para agendar cita" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $appointmentsNumerousCallsScheduleAppointment = $appointmentsNumerousCallsScheduleAppoint->first()->total_general;

        // No recibio alertamiento de servicio
        $appointmentsDidNotReceiveServiceA = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Citas" AND tipo_queja = "No recibio alertamiento de servicio", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Citas" AND tipo_queja_2 = "No recibio alertamiento de servicio", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Citas" OR area_2 = "Citas")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Citas" AS area_1, "No recibio alertamiento de servicio" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $appointmentsDidNotReceiveServiceAlert = $appointmentsDidNotReceiveServiceA->first()->total_general;

        // Demora para obtener cita
        $appointmentsDelayGettingAppoint= DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Citas" AND tipo_queja = "Demora para obtener cita", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Citas" AND tipo_queja_2 = "Demora para obtener cita", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Citas" OR area_2 = "Citas")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Citas" AS area_1, "Demora para obtener cita" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $appointmentsDelayGettingAppointment = $appointmentsDelayGettingAppoint->first()->total_general;

        // Error en la cita
        $appointmentsQuoteErr = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Citas" AND tipo_queja = "Error en la cita", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Citas" AND tipo_queja_2 = "Error en la cita", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Citas" OR area_2 = "Citas")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Citas" AS area_1, "Error en la cita" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $appointmentsQuoteError = $appointmentsQuoteErr->first()->total_general;

        // Precio diferente al otorgado en llamada
        $appointmentsPriceDifferentGivenInC = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Citas" AND tipo_queja = "Precio diferente al otorgado en llamada", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Citas" AND tipo_queja_2 = "Precio diferente al otorgado en llamada", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Citas" OR area_2 = "Citas")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Citas" AS area_1, "Precio diferente al otorgado en llamada" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $appointmentsPriceDifferentGivenInCall = $appointmentsPriceDifferentGivenInC->first()->total_general;

        $totalAppointments = $appointmentsNumerousCallsScheduleAppointment + $appointmentsDidNotReceiveServiceAlert + $appointmentsDelayGettingAppointment +
                             $appointmentsQuoteError + $appointmentsPriceDifferentGivenInCall;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'appointmentsNumerousCallsScheduleAppointment' => $appointmentsNumerousCallsScheduleAppointment,
                'appointmentsDidNotReceiveServiceAlert' => $appointmentsDidNotReceiveServiceAlert,
                'appointmentsDelayGettingAppointment' => $appointmentsDelayGettingAppointment,
                'appointmentsQuoteError' => $appointmentsQuoteError,
                'appointmentsPriceDifferentGivenInCall' => $appointmentsPriceDifferentGivenInCall,
                'totalAppointments' => $totalAppointments
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaSales(){
        $mes_actual = $this->mes_actual;

        // Ventas
        // Información errónea
        $salesDisinfo = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Ventas" AND tipo_queja = "Información errónea", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Ventas" AND tipo_queja_2 = "Información errónea", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Ventas" OR area_2 = "Ventas")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Ventas" AS area_1, "Información errónea" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $salesDisinformation = $salesDisinfo->first()->total_general;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'salesDisinformation' => $salesDisinformation
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaProduct(){
        $mes_actual = $this->mes_actual;

        // Product
        // Mala calidad del producto
        $productPoorProductQual = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Producto" AND tipo_queja = "Mala calidad del producto", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Producto" AND tipo_queja_2 = "Mala calidad del producto", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Producto" OR area_2 = "Producto")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Producto" AS area_1, "Mala calidad del producto" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $productPoorProductQuality = $productPoorProductQual->first()->total_general;

        if (isset($productPoorProductQuality)) {
            $productPoorProductQuality;
        }else {
            $productPoorProductQuality = 0;
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'productPoorProductQuality' => $productPoorProductQuality
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaReplacementParts(){
        $mes_actual = $this->mes_actual;

        // Refacciones
        // Espera prolongada de refacciones
        $sparePartsLongW = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Refacciones" AND tipo_queja = "Espera prolongada de refacciones", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Refacciones" AND tipo_queja_2 = "Espera prolongada de refacciones", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Refacciones" OR area_2 = "Refacciones")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Refacciones" AS area_1, "Espera prolongada de refacciones" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $sparePartsLongWait = $sparePartsLongW->first()->total_general;

        // Reparacion pendiente por falta de refacciones
        $sparePartsPendingRep = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Refacciones" AND tipo_queja = "Reparacion pendiente por falta de refacciones", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Refacciones" AND tipo_queja_2 = "Reparacion pendiente por falta de refacciones", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Refacciones" OR area_2 = "Refacciones")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Refacciones" AS area_1, "Reparacion pendiente por falta de refacciones" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $sparePartsPendingRepair = $sparePartsPendingRep->first()->total_general;

        // Disponibilidad de refacciones
        $sparePartsAvailab = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Refacciones" AND tipo_queja = "Disponibilidad de refacciones", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Refacciones" AND tipo_queja_2 = "Disponibilidad de refacciones", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Refacciones" OR area_2 = "Refacciones")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Refacciones" AS area_1, "Disponibilidad de refacciones" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $sparePartsAvailability = $sparePartsAvailab->first()->total_general;

        $totalSpareParts = $sparePartsLongWait + $sparePartsPendingRepair + $sparePartsAvailability;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'sparePartsLongWait' => $sparePartsLongWait,
                'sparePartsPendingRepair' => $sparePartsPendingRepair,
                'sparePartsAvailability' => $sparePartsAvailability,
                'totalSpareParts' => $totalSpareParts,
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaReception(){
        $mes_actual = $this->mes_actual;

        // Recepción
        // Demora en recepción
        $receptionD = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Recepción" AND tipo_queja = "Demora en recepción", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Recepción" AND tipo_queja_2 = "Demora en recepción", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Recepción" OR area_2 = "Recepción")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Recepción" AS area_1, "Demora en recepción" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $receptionDelay = $receptionD->first()->total_general;

        if (isset($receptionDelay)) {
            $receptionDelay;
        }else{
            $receptionDelay = 0;
        }

        // No ofrecieron amenidades
        $receptionOfferedNoAmen = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Recepción" AND tipo_queja = "No ofrecieron amenidades", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Recepción" AND tipo_queja_2 = "No ofrecieron amenidades", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Recepción" OR area_2 = "Recepción")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Recepción" AS area_1, "No ofrecieron amenidades" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $receptionOfferedNoAmenities = $receptionOfferedNoAmen->first()->total_general;

        if (isset($receptionOfferedNoAmenities)) {
            $receptionOfferedNoAmenities;
        }else{
            $receptionOfferedNoAmenities = 0;
        }

        $totalReception = $receptionDelay + $receptionOfferedNoAmenities;

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'receptionDelay' => $receptionDelay,
                'receptionOfferedNoAmenities' => $receptionOfferedNoAmenities,
                'totalReception' => $totalReception
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function customerComplaintsByTypeAreaFacilities(){
        $mes_actual = $this->mes_actual;

        // Instalaciones
        // Espera no grata
        $facilitiesUnpleasantW = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Instalaciones" AND tipo_queja = "Espera no grata", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Instalaciones" AND tipo_queja_2 = "Espera no grata", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Instalaciones" OR area_2 = "Instalaciones")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Instalaciones" AS area_1, "Espera no grata" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $facilitiesUnpleasantWait = $facilitiesUnpleasantW->first()->total_general;

        if (isset($facilitiesUnpleasantWait)) {
            $facilitiesUnpleasantWait;
        }else{
            $facilitiesUnpleasantWait = 0;
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'facilitiesUnpleasantWait' => $facilitiesUnpleasantWait
            ] 
        ];

        return response()->json($response, $response['code']);
    }

    public function trackingWorkshopComplaints(){
        $mes_actual = $this->mes_actual;
        $mes_primero = $this->mes_primero;
        $mes_segundo = $this->mes_segundo;

        // Búsqueda de Quejas taller
        $workshopComplaints = ServicioTemp::where('area_1', 'Taller')
                                            ->whereNotNull('tipo_queja')
                                            ->where('tipo_queja', '!=', 'Visita repetida')
                                            // ->where('tipo_queja', '!=', 'Demora en reparación')
                                            // ->orWhere('tipo_queja', '=', 'Demora en reparación')
                                            ->distinct()->pluck('tipo_queja');

        $monthSources = [$mes_primero, $mes_segundo, $mes_actual];

        /** ################################ Mes primero ############################### */
        // Taller
        // Nueva falla después de la reparación
        $workshopNewFailAfterRepFirstMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Nueva falla después de la reparación", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Nueva falla después de la reparación", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_primero AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Nueva falla después de la reparación" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_primero' => $mes_primero])
        ->get();

        $workshopNewFailAfterReparationFirstMonth = $workshopNewFailAfterRepFirstMonth->first()->total_general !== null ? $workshopNewFailAfterRepFirstMonth->first()->total_general : 0;

        // Falla persistente
        $persistentFailFirstMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Falla persistente", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Falla persistente", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_primero AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Falla persistente" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_primero' => $mes_primero])
        ->get();

        $persistentFailureFirstMonth = $persistentFailFirstMonth->first()->total_general !== null ? $persistentFailFirstMonth->first()->total_general : 0;

        // Daño generado en servicio
        $damageGeneratedServFirstMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Daño generado en servicio", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Daño generado en servicio", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_primero AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Daño generado en servicio" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_primero' => $mes_primero])
        ->get();

        $damageGeneratedServiceFirstMonth = $damageGeneratedServFirstMonth->first()->total_general !== null ? $damageGeneratedServFirstMonth->first()->total_general : 0;

        // Demora en diagnostico
        $delayDiagFirstMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Demora en diagnostico", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Demora en diagnostico", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_primero AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Demora en diagnostico" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_primero' => $mes_primero])
        ->get();

        $delayDiagnosisFirstMonth = $delayDiagFirstMonth->first()->total_general !== null ? $delayDiagFirstMonth->first()->total_general: 0;

        // Demora en reparación/mantenimiento
        $delayRepairMaintenanFirstMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Demora en reparación", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Demora en reparación", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_primero AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Demora en reparación" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_primero' => $mes_primero])
        ->get();

        $delayRepairMaintenanceFirstMonth = $delayRepairMaintenanFirstMonth->first()->total_general !== null ? $delayRepairMaintenanFirstMonth->first()->total_general : 0;

        // Fallo en diagnóstico
        $diagnosticFailFirstMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Fallo en diagnostico", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Fallo en diagnostico", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_primero AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Fallo en diagnostico" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_primero' => $mes_primero])
        ->get();

        $diagnosticFailureFirstMonth = $diagnosticFailFirstMonth->first()->total_general != null ? $diagnosticFailFirstMonth->first()->total_general : 0;
        // ********************************************************************************

        /** ################################ Mes segundo ############################### */
        // Nueva falla después de la reparación
        $workshopNewFailAfterRepSecondMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Nueva falla después de la reparación", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Nueva falla después de la reparación", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_segundo AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Nueva falla después de la reparación" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_segundo' => $mes_segundo])
        ->get();

        $workshopNewFailAfterReparationSecondMonth = $workshopNewFailAfterRepSecondMonth->first()->total_general !== null ? $workshopNewFailAfterRepSecondMonth->first()->total_general : 0;

        // Falla persistente
        $persistentFailSecondMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Falla persistente", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Falla persistente", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_segundo AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Falla persistente" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_segundo' => $mes_segundo])
        ->get();

        $persistentFailureSecondMonth = $persistentFailSecondMonth->first()->total_general !== null ? $persistentFailSecondMonth->first()->total_general : 0;

        // Daño generado en servicio
        $damageGeneratedServSecondMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Daño generado en servicio", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Daño generado en servicio", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_segundo AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Daño generado en servicio" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_segundo' => $mes_segundo])
        ->get();

        $damageGeneratedServiceSecondMonth = $damageGeneratedServSecondMonth->first()->total_general !== null ? $damageGeneratedServSecondMonth->first()->total_general : 0;

        // Demora en diagnostico
        $delayDiagSecondMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Demora en diagnostico", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Demora en diagnostico", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_segundo AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Demora en diagnostico" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_segundo' => $mes_segundo])
        ->get();

        $delayDiagnosisSecondMonth = $delayDiagSecondMonth->first()->total_general != null ? $delayDiagSecondMonth->first()->total_general : 0;

        // Demora en reparación/mantenimiento
        $delayRepairMaintenanSecondMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Demora en reparación", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Demora en reparación", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_segundo AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Demora en reparación" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_segundo' => $mes_segundo])
        ->get();

        $delayRepairMaintenanceSecondMonth = $delayRepairMaintenanSecondMonth->first()->total_general != null ? $delayRepairMaintenanSecondMonth->first()->total_general: 0;

        // Fallo en diagnóstico
        $diagnosticFailSecondMonth = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Fallo en diagnostico", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Fallo en diagnostico", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_segundo AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Fallo en diagnostico" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_segundo' => $mes_segundo])
        ->get();

        $diagnosticFailureSecondMonth = $diagnosticFailSecondMonth->first()->total_general != null ? $diagnosticFailSecondMonth->first()->total_general : 0;
        // ********************************************************************************

        /** ################################ Mes actual ################################ */
        // Taller
        // Nueva falla después de la reparación
        $workshopNewFailAfterRep = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Nueva falla después de la reparación", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Nueva falla después de la reparación", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Nueva falla después de la reparación" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $workshopNewFailAfterReparation = $workshopNewFailAfterRep->first()->total_general !== null ? $workshopNewFailAfterRep->first()->total_general : 0;

        // Falla persistente
        $persistentFail = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Falla persistente", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Falla persistente", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Falla persistente" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $persistentFailure = $persistentFail->first()->total_general !== null ? $persistentFail->first()->total_general : 0;

        // Daño generado en servicio
        $damageGeneratedServ = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Daño generado en servicio", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Daño generado en servicio", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Daño generado en servicio" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $damageGeneratedService = $damageGeneratedServ->first()->total_general !== null ? $damageGeneratedServ->first()->total_general : 0;

        // Demora en diagnostico
        $delayDiag = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Demora en diagnostico", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Demora en diagnostico", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Demora en diagnostico" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $delayDiagnosis = $delayDiag->first()->total_general !== null ? $delayDiag->first()->total_general : 0;

        // Demora en reparación/mantenimiento
        $delayRepairMaintenan = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Demora en reparación", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Demora en reparación", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Demora en reparación" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $delayRepairMaintenance = $delayRepairMaintenan->first()->total_general !== null ? $delayRepairMaintenan->first()->total_general : 0;

        // Fallo en diagnóstico
        $diagnosticFail = DB::table(DB::raw('(
            SELECT 
                area_1, 
                tipo_queja, 
                area_2,
                tipo_queja_2,
                SUM(IF(area_1 = "Taller" AND tipo_queja = "Fallo en diagnostico", 1, 0)) AS total_tipo_queja,
                SUM(IF(area_2 = "Taller" AND tipo_queja_2 = "Fallo en diagnostico", 1, 0)) AS total_tipo_queja_2
            FROM servicio_temps
            WHERE mes = :mes_actual AND (area_1 = "Taller" OR area_2 = "Taller")
            GROUP BY area_1, tipo_queja, area_2, tipo_queja_2
        ) AS subconsulta'))
        ->selectRaw('"Taller" AS area_1, "Fallo en diagnostico" AS tipo_queja')
        ->selectRaw('SUM(total_tipo_queja) AS total_tipo_queja')
        ->selectRaw('SUM(total_tipo_queja_2) AS total_tipo_queja_2')
        ->selectRaw('SUM(total_tipo_queja + total_tipo_queja_2) AS total_general')
        ->setBindings(['mes_actual' => $mes_actual])
        ->get();

        $diagnosticFailure = $diagnosticFail->first()->total_general !== null ? $diagnosticFail->first()->total_general : 0;
        // **********************************************************************************************

        $workshopComplaintsArray =  $workshopComplaints->toArray();
        $complaintType = [
            $mes_primero => [
                $workshopComplaintsArray[0] => $workshopNewFailAfterReparationFirstMonth,
                $workshopComplaintsArray[1] => $persistentFailureFirstMonth,
                $workshopComplaintsArray[2] => $delayDiagnosisFirstMonth,
                $workshopComplaintsArray[3] => $diagnosticFailureFirstMonth,
                $workshopComplaintsArray[4] => $delayRepairMaintenanceFirstMonth,
                $workshopComplaintsArray[5] => $damageGeneratedServiceFirstMonth
            ],
            $mes_segundo => [
                $workshopComplaintsArray[0] => $workshopNewFailAfterReparationSecondMonth,
                $workshopComplaintsArray[1] => $persistentFailureSecondMonth,
                $workshopComplaintsArray[2] => $delayDiagnosisSecondMonth,
                $workshopComplaintsArray[3] => $diagnosticFailureSecondMonth,
                $workshopComplaintsArray[4] => $delayRepairMaintenanceSecondMonth,
                $workshopComplaintsArray[5] => $damageGeneratedServiceSecondMonth
            ],
            $mes_actual => [
                $workshopComplaintsArray[0] => $workshopNewFailAfterReparation,
                $workshopComplaintsArray[1] => $persistentFailure,
                $workshopComplaintsArray[2] => $delayDiagnosis,
                $workshopComplaintsArray[3] => $diagnosticFailure,
                $workshopComplaintsArray[4] => $delayRepairMaintenance,
                $workshopComplaintsArray[5] => $damageGeneratedService
            ],
        ];

        $seriesFormat = [];
        
        foreach ($monthSources as $month) {
            $monthData = [];
            foreach ($workshopComplaints as $complaint) {
                $monthData[$complaint] = 0;
            }
            $seriesFormat[] = [
                'name' => $month,
                'data' => $monthData,
            ];
        }

        foreach ($complaintType as $month => $complaints) {
            foreach ($complaints as $complaint => $value) {
                $value = intval($value);
                foreach ($seriesFormat as &$series) {
                    if ($series['name'] === $month) {
                        $series['data'][$complaint] = $value;
                        break;
                    }
                }
            }
        }

        // Eliminar los ceros de la inicialización
        foreach ($seriesFormat as &$series) {
            $series['data'] = array_values($series['data']);
        }


        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [

                'workshopComplaints' => $workshopComplaints,

                'category' => $monthSources,

                'seriesFormat' => $seriesFormat,

                'Total ' . $mes_primero => $workshopNewFailAfterReparationFirstMonth + $persistentFailureFirstMonth + $delayDiagnosisFirstMonth + $diagnosticFailureFirstMonth + $delayRepairMaintenanceFirstMonth + $damageGeneratedServiceFirstMonth,
                'Total ' . $mes_segundo => $workshopNewFailAfterReparationSecondMonth + $persistentFailureSecondMonth + $delayDiagnosisSecondMonth + $diagnosticFailureSecondMonth + $delayRepairMaintenanceSecondMonth + $damageGeneratedServiceSecondMonth,
                'Total ' . $mes_actual => $workshopNewFailAfterReparation + $persistentFailure + $delayDiagnosis + $diagnosticFailure + $delayRepairMaintenance + $damageGeneratedService

                // 'mes_primero' => $mes_primero,
                // 'workshopNewFailAfterReparationFirstMonth' => $workshopNewFailAfterReparationFirstMonth, /** $workshopNewFailAfterRepFirstMonth[0]->total_general, */  
                // 'persistentFailureFirstMonth' => $persistentFailureFirstMonth,
                // 'damageGeneratedServiceFirstMonth' => $damageGeneratedServiceFirstMonth,
                // 'delayDiagnosisFirstMonth' => $delayDiagnosisFirstMonth,
                // 'delayRepairMaintenanceFirstMonth' => $delayRepairMaintenanceFirstMonth,
                // 'diagnosticFailureFirstMonth' => $diagnosticFailureFirstMonth,

                // 'mes_segundo' => $mes_segundo,
                // 'workshopNewFailAfterReparationSecondMonth' => $workshopNewFailAfterReparationSecondMonth,
                // 'persistentFailureSecondMonth' => $persistentFailureSecondMonth,
                // 'damageGeneratedServiceSecondMonth' => $damageGeneratedServiceSecondMonth,
                // 'delayDiagnosisSecondMonth' => $delayDiagnosisSecondMonth,
                // 'delayRepairMaintenanceSecondMonth' => $delayRepairMaintenanceSecondMonth,
                // 'diagnosticFailureSecondMonth' => $diagnosticFailureSecondMonth,

                // 'mes_actual' => $mes_actual,
                // 'workshopNewFailAfterReparation' => $workshopNewFailAfterReparation,
                // 'persistentFailure' => $persistentFailure,
                // 'damageGeneratedService' => $damageGeneratedService,
                // 'delayDiagnosis' => $delayDiagnosis,
                // 'delayRepairMaintenance' => $delayRepairMaintenance,
                // 'diagnosticFailure' => $diagnosticFailure

            ]
        ];
    
        return response()->json($response, $response['code']);
    }

    public function complaintsAdvisorPointContact(){
        $mes_actual = $this->mes_actual;

        // Búsqueda de asesores
        $executives = ServicioTemp::distinct()->pluck('asesor');

        $asesores =  $executives->toArray();

        // Búsqueda de áreas
        $areasResult = DB::table('servicio_temps')
                    ->whereIn('area_1', [
                        'Taller', 'Asesor', 'Garantias', 'Entregas', 'Lavado',
                        'Servicio General', 'Caja', 'Citas', 'Ventas'
                    ])
                    ->groupBy('area_1')
                    ->pluck('area_1');

        $areas = $areasResult->toArray();
        
        $countAsesorAreas = DB::table(DB::raw('(SELECT ' . implode(' UNION ALL SELECT ', array_map(function ($asesor) {
        return "'$asesor' as asesor";
        }, $asesores)) . ') as asesores'))
        ->crossJoin(DB::raw('(SELECT ' . implode(' UNION ALL SELECT ', array_map(function ($area) {
            return "'$area' as area";
        }, $areas)) . ') as areas'))
        ->leftJoin(DB::raw('(SELECT asesor, area_1 as area, count(area_1) as area_count FROM servicio_temps
            WHERE mes = \'' . $mes_actual . '\'
            GROUP BY asesor, area_1
            UNION ALL
            SELECT asesor, area_2 as area, count(area_2) as area_count FROM servicio_temps
            WHERE mes = \'' . $mes_actual . '\'
            GROUP BY asesor, area_2) as servicio_temps'), function ($join) {
            $join->on('asesores.asesor', '=', 'servicio_temps.asesor')
                ->on('areas.area', '=', 'servicio_temps.area');
        })
        ->select('asesores.asesor', 'areas.area', DB::raw('IFNULL(SUM(servicio_temps.area_count), 0) as total_count'))
        ->groupBy('asesores.asesor', 'areas.area')
        ->orderBy('asesores.asesor')
        ->get();

        $seriesFormat = [];
        foreach ($areas as $area) {
            $seriesFormat[] = [
                'name' => $area,
                'data' => array_fill(0, count($executives), 0)
            ];
        }

        foreach ($asesores as $key => $executive) {
            foreach ($countAsesorAreas as $countAsesorArea) {
                if ($countAsesorArea->asesor === $executive) {
                    $executiveIndex = $key;
                    $areaResultIndex = array_search($countAsesorArea->area, array_column($seriesFormat, 'name'));
                    if ($areaResultIndex !== false) {
                        $seriesFormat[$areaResultIndex]['data'][$executiveIndex] = $countAsesorArea->total_count;
                    }
                }
            }
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'countAsesorAreas' => $countAsesorAreas,
                'executives' => $executives,
                '$areasResult' => $areasResult,
                'seriesFormat' => $seriesFormat
            ]
        ];
        
        return response()->json($response, $response['code']);
    }
    
}
