<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
// Excel
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CreateLeadTempImport;
use App\Imports\CreateCarteraTempImport;

use Illuminate\Support\Carbon;

use App\Models\LeadTemp;
use App\Models\CarteraTemp;

class DashboardLeadsCarteraController extends Controller
{

    public function storeCarteraTemp( Request $request ){
        $file = $request->file('file');
        
        Excel::import(new CreateCarteraTempImport, $file);
        
        $response = Session::get('response');
        
        $data = array(
            'code' => 200,
            'status' => 'success',
            'respuesta' => $response
        );
        return response()->json($data, $data['code']);
    }

    public function storeLeadsTemp( Request $request ){
        $file = $request->file('file');
        
        Excel::import(new CreateLeadTempImport, $file);
        
        $response = Session::get('response');
        
        $data = array(
            'code' => 200,
            'status' => 'success',
            'respuesta' => $response
        );
        return response()->json($data, $data['code']);
    }

    public function getLeadsIncidencesMetrics(){

        $totalGlobalLeads = LeadTemp::where('mes', 'Marzo')->count();

        $totalIncidences = LeadTemp::where('mes', 'Marzo')->whereNotNull('queja_ticket')->count('queja_ticket');

        $categories = LeadTemp::where('mes', 'Marzo')->whereNotNull('queja_ticket')->distinct()->pluck('queja_ticket')->toArray();

        $departments = LeadTemp::where('mes', 'Marzo')->whereNotNull('departamento')->distinct()->pluck('departamento')->toArray();

        $series = LeadTemp::where('mes', 'Marzo')
            ->selectRaw('queja_ticket, departamento, COUNT(*) AS total_incidences')
            ->whereNotNull('queja_ticket')
            ->groupBy('queja_ticket', 'departamento')
            ->get();

        $seriesFormat = [];
        $totalIncidencesByDepartment = [];

        foreach ($departments as $department) {
            $seriesFormat[$department]['name'] = $department;
            $seriesFormat[$department]['data'] = array_fill(0, count($categories), 0);
            $totalIncidencesByDepartment[$department] = 0;
        }

        foreach ($series as $item) {
            $department = $item->departamento;
            $categoryIndex = array_search($item->queja_ticket, $categories);

            $seriesFormat[$department]['data'][$categoryIndex] = $item->total_incidences;
            $totalIncidencesByDepartment[$department] += $item->total_incidences;
        }

        $departmentPercentages = [];
        foreach ($totalIncidencesByDepartment as $totalIncidencesDep) {
            $percentage = round(($totalIncidencesDep / $totalIncidences) * 100, 2);
            $departmentPercentages[] = $percentage;
        }

        foreach ($seriesFormat as &$departmentCounts) {
            $departmentCounts['data'] = array_values($departmentCounts['data']);
        }

        $totalLeadsByDepartment = LeadTemp::where('mes', 'Marzo')
            ->whereNotNull('departamento')
            ->selectRaw('departamento, COUNT(*) as total_leads')
            ->groupBy('departamento')
            ->get()
            ->map(function ($item) {
                return ['category' => $item->departamento, 'value' => $item->total_leads];
            })
            ->values()
            ->toArray();

        $totalLeadsAssignedByDepartment = LeadTemp::where('mes', 'Marzo')
            ->where('asignado', 'SI')
            ->selectRaw('departamento, COUNT(*) as total_leads_assigned')
            ->groupBy('departamento')
            ->get()
            ->map(function ($item) {
                return ['category' => $item->departamento, 'value' => $item->total_leads_assigned];
            })
            ->values()
            ->toArray();

        $totalLeadsContactedByDepartment = LeadTemp::where('mes', 'Marzo')
            ->where('estatus_experiencia', 'CONTACTADO')
            ->selectRaw('departamento, COUNT(*) as total_leads_contacted')
            ->groupBy('departamento')
            ->get()
            ->map(function ($item) {
                return ['category' => $item->departamento, 'value' => $item->total_leads_contacted];
            })
            ->values()
            ->toArray();
        
        foreach ($totalLeadsContactedByDepartment as &$department) {
            $totalLeadsDepartment = collect($totalLeadsByDepartment)
                ->where('category', $department['category'])
                ->pluck('value')
                ->first(); 

            if ($totalLeadsDepartment === null || $totalLeadsDepartment == 0) {
                $percentage = 0;
            } else {
                $percentage = round(($department['value'] / $totalLeadsDepartment) * 100, 2);
            }

            $department['percentage'] = $percentage;
        }


        $totalLeadsSatisfiedByDepartment = LeadTemp::where('mes', 'Marzo')
            ->where('calificacion_csi', '5')
            ->selectRaw('departamento, COUNT(*) as total_cartera_satisfied')
            ->groupBy('departamento')
            ->get()
            ->map(function ($item) {
                return ['category' => $item->departamento, 'value' => $item->total_cartera_satisfied];
            })
            ->values()
            ->toArray();

        foreach ($totalLeadsSatisfiedByDepartment as &$department) {
            $totalLeadsDepartment = collect($totalLeadsByDepartment)
                ->where('category', $department['category'])
                ->pluck('value')
                ->first();
            
                if ($totalLeadsDepartment === null || $totalLeadsDepartment == 0) {
                    $percentage = 0;
                } else {
                    $percentage = round(($department['value'] / $totalLeadsDepartment) * 100, 2);
                }

            $department['percentage'] = $percentage;
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'totalGlobalLeads' => $totalGlobalLeads,
                'totalIncidences' => $totalIncidences,
                'departmentPercentages' => $departmentPercentages,
                'categories' => $categories,
                'series' => array_values($seriesFormat),
                'totalLeadsByDepartment' => $totalLeadsByDepartment,
                'totalLeadsAssignedByDepartment' => $totalLeadsAssignedByDepartment,
                'totalLeadsContactedByDepartment' => $totalLeadsContactedByDepartment,
                'totalLeadsSatisfiedByDepartment' => $totalLeadsSatisfiedByDepartment
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getCarteraIncidencesMetrics(){

        $totalGlobalCartera = CarteraTemp::where('mes', 'Marzo')->count();

        $totalIncidences = CarteraTemp::where('mes', 'Marzo')->whereNotNull('queja_ticket')->count('queja_ticket');

        $categories = CarteraTemp::where('mes', 'Marzo')->whereNotNull('queja_ticket')->distinct()->pluck('queja_ticket')->toArray();

        $departments = CarteraTemp::where('mes', 'Marzo')->whereNotNull('departamento')->distinct()->pluck('departamento')->toArray();

        $series = CarteraTemp::where('mes', 'Marzo')
            ->selectRaw('queja_ticket, departamento, COUNT(*) AS total_incidences')
            ->whereNotNull('queja_ticket')
            ->groupBy('queja_ticket', 'departamento')
            ->get();

        $seriesFormat = [];
        $totalIncidencesByDepartment = [];

        foreach ($departments as $department) {
            $seriesFormat[$department]['name'] = $department;
            $seriesFormat[$department]['data'] = array_fill(0, count($categories), 0);
            $totalIncidencesByDepartment[$department] = 0;
        }

        foreach ($series as $item) {
            $department = $item->departamento;
            $categoryIndex = array_search($item->queja_ticket, $categories);

            $seriesFormat[$department]['data'][$categoryIndex] = $item->total_incidences;
            $totalIncidencesByDepartment[$department] += $item->total_incidences;
        }

        $departmentPercentages = [];
        foreach ($totalIncidencesByDepartment as $totalIncidencesDep) {
            $percentage = round(($totalIncidencesDep / $totalIncidences) * 100, 2);
            $departmentPercentages[] = $percentage;
        }

        foreach ($seriesFormat as &$departmentCounts) {
            $departmentCounts['data'] = array_values($departmentCounts['data']);
        }

        $totalCarteraByDepartment = CarteraTemp::where('mes', 'Marzo')
            ->selectRaw('departamento, COUNT(*) as total_cartera')
            ->groupBy('departamento')
            ->get()
            ->map(function ($item) {
                return ['category' => $item->departamento, 'value' => $item->total_cartera];
            })
            ->values()
            ->toArray();

        $totalCarteraContactedByDepartment = CarteraTemp::where('mes', 'Marzo')
            ->where('estatus_contactado', 'CONTACTADO')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get()
            ->map(function ($item) {
                return ['category' => $item->departamento, 'value' => $item->total_cartera_contacted];
            })
            ->values()
            ->toArray();
        
        foreach ($totalCarteraContactedByDepartment as &$department) {
            $totalCarteraDepartment = collect($totalCarteraByDepartment)
                ->where('category', $department['category'])
                ->pluck('value')
                ->first();

            $percentage = round(($department['value'] / $totalCarteraDepartment) * 100, 2);

            $department['percentage'] = $percentage;
        }


        $totalCarteraSatisfiedByDepartment = CarteraTemp::where('mes', 'Marzo')
            ->where('calificacion_csi', '5')
            ->selectRaw('departamento, COUNT(*) as total_cartera_satisfied')
            ->groupBy('departamento')
            ->get()
            ->map(function ($item) {
                return ['category' => $item->departamento, 'value' => $item->total_cartera_satisfied];
            })
            ->values()
            ->toArray();

        foreach ($totalCarteraSatisfiedByDepartment as &$department) {
            $totalCarteraDepartment = collect($totalCarteraByDepartment)
                ->where('category', $department['category'])
                ->pluck('value')
                ->first();

            $percentage = round(($department['value'] / $totalCarteraDepartment) * 100, 2);

            $department['percentage'] = $percentage;
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'totalGlobalCartera' => $totalGlobalCartera,
                'totalIncidences' => $totalIncidences,
                'departmentPercentages' => $departmentPercentages,
                'categories' => $categories,
                'series' => array_values($seriesFormat),
                'totalsByDepartment' => $totalCarteraByDepartment,
                'totalCarteraContactedByDepartment' => $totalCarteraContactedByDepartment,
                'totalCarteraSatisfiedByDepartment' => $totalCarteraSatisfiedByDepartment
            ],
        ];

        return response()->json($response, $response['code']);
    }

    public function getSourceLeadMetrics(){

        $series = LeadTemp::where('mes', 'Marzo')
            ->selectRaw('fuente, tipo, COUNT(DISTINCT id) AS total_leads')
            ->whereNotNull('departamento')
            ->groupBy('fuente', 'tipo')
            ->get();

        $types = LeadTemp::where('mes','Marzo')->whereNotNull('tipo')->distinct()->pluck('tipo')->toArray();

        $sources = LeadTemp::where('mes', 'Marzo')->whereNotNull('fuente')->distinct()->pluck('fuente')->toArray();
                
        $leadsByTypeAndSource = [];

        foreach ($types as $type) {
            $leadsByTypeAndSource[$type] = array_fill(0, count($sources), 0);
        }

        foreach ($series as $lead) {
            $tipo = $lead->tipo;
            $fuente = $lead->fuente;
            $totalLeads = $lead->total_leads;
        
            $index = array_search($fuente, $sources);
        
            $leadsByTypeAndSource[$tipo][$index] += $totalLeads;
        }

        $totalVector = array_fill(0, count($sources), 0);

        foreach ($leadsByTypeAndSource as $vector) {
            for ($i = 0; $i < count($vector); $i++) {
                $totalVector[$i] += $vector[$i];
            }
        }

        $total = array_sum($totalVector);

        if ($total != 0) {
            foreach ($totalVector as $value) {
                $percentage = round(($value / $total) * 100, 2);
                $percentages[] = $percentage;
            }
        } else {
            $percentages = array_fill(0, count($totalVector), 0);
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $sources,
                'series' => $leadsByTypeAndSource,
                'totales' => $totalVector,
                'percentages' => $percentages,
                'total' => $total
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getAssignedLeadsByExecutiveMetrics(){

        $series = LeadTemp::where('mes', 'Marzo')
            ->selectRaw('ejecutivo, tipo, COUNT(DISTINCT id) AS total_leads')
            ->whereNotNull('ejecutivo')
            ->groupBy('ejecutivo', 'tipo')
            ->get();
        
        $types = LeadTemp::where('mes','Marzo')->whereNotNull('tipo')->distinct()->pluck('tipo')->toArray();

        $executives = LeadTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();
                
        $leadsByTypeAndExecutive = [];

        foreach ($types as $type) {
            $leadsByTypeAndExecutive[$type] = array_fill(0, count($executives), 0);
        }

        foreach ($series as $lead) {
            $tipo = $lead->tipo;
            $ejecutivo = $lead->ejecutivo;
            $totalLeads = $lead->total_leads;
        
            $index = array_search($ejecutivo, $executives);
        
            $leadsByTypeAndExecutive[$tipo][$index] += $totalLeads;
        }

        $totalVector = array_fill(0, count($executives), 0);

        foreach ($leadsByTypeAndExecutive as $vector) {
            for ($i = 0; $i < count($vector); $i++) {
                $totalVector[$i] += $vector[$i];
            }
        }


        $total = array_sum($totalVector);

        if ($total != 0) {
            foreach ($totalVector as $value) {
                $percentage = round(($value / $total) * 100, 2);
                $percentages[] = $percentage;
            }
        } else {
            $percentages = array_fill(0, count($totalVector), 0);
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $executives,
                'series' => $leadsByTypeAndExecutive,
                'totales' => $totalVector,
                'percentages' => $percentages,
                'total' => $total
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getAssignedCarteraBySourceAndDepartment(){

        $series = CarteraTemp::where('mes', 'Marzo')
            ->selectRaw('forma_contacto, departamento, COUNT(DISTINCT id) AS total_leads')
            ->whereNotNull('forma_contacto')
            ->groupBy('forma_contacto', 'departamento')
            ->get();

        $contactMethods = CarteraTemp::where('mes', 'Marzo')->whereNotNull('forma_contacto')->distinct()->pluck('forma_contacto')->toArray();

        $departments = CarteraTemp::where('mes', 'Marzo')->whereNotNull('departamento')->distinct()->pluck('departamento')->toArray();

        $leadsBySourceAndContactMethod = [];

        foreach ($contactMethods as $contactMethod) {
            $leadsBySourceAndContactMethod[$contactMethod] = array_fill(0, count($departments), 0);
        }
        
        foreach ($series as $lead) {
            $formaContacto = $lead->forma_contacto;
            $departamento = $lead->departamento;
            $totalLeads = $lead->total_leads;
        
            $index = array_search($departamento, $departments);
        
            $leadsBySourceAndContactMethod[$formaContacto][$index] += $totalLeads;
        }

        $totalVector = array_fill(0, count($departments), 0);

        foreach ($leadsBySourceAndContactMethod as $vector) {
            for ($i = 0; $i < count($vector); $i++) {
                $totalVector[$i] += $vector[$i];
            }
        }


        $total = array_sum($totalVector);

        if ($total != 0) {
            foreach ($totalVector as $value) {
                $percentage = round(($value / $total) * 100, 2);
                $percentages[] = $percentage;
            }
        } else {
            $percentages = array_fill(0, count($totalVector), 0);
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $departments,
                'series' => $leadsBySourceAndContactMethod,
                'totales' => $totalVector,
                'percentages' => $percentages,
                'total' => $total
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getAssignedCarteraBySourceAndExecutive(){

        $series = CarteraTemp::where('mes', 'Marzo')
            ->selectRaw('forma_contacto, ejecutivo, COUNT(DISTINCT id) AS total_leads')
            ->whereNotNull('forma_contacto')
            ->groupBy('forma_contacto', 'ejecutivo')
            ->get();

        $contactMethods = CarteraTemp::where('mes', 'Marzo')->whereNotNull('forma_contacto')->distinct()->pluck('forma_contacto')->toArray();

        $executives = CarteraTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();

        $carteraBySourceAndExecutive = [];

        foreach ($contactMethods as $contactMethod) {
            $carteraBySourceAndExecutive[$contactMethod] = array_fill(0, count($executives), 0);
        }
        
        foreach ($series as $lead) {
            $formaContacto = $lead->forma_contacto;
            $ejecutivo = $lead->ejecutivo;
            $totalLeads = $lead->total_leads;
        
            $index = array_search($ejecutivo, $executives);
        
            $carteraBySourceAndExecutive[$formaContacto][$index] += $totalLeads;
        }

        $totalVector = array_fill(0, count($executives), 0);

        foreach ($carteraBySourceAndExecutive as $vector) {
            for ($i = 0; $i < count($vector); $i++) {
                $totalVector[$i] += $vector[$i];
            }
        }


        $total = array_sum($totalVector);

        if ($total != 0) {
            foreach ($totalVector as $value) {
                $percentage = round(($value / $total) * 100, 2);
                $percentages[] = $percentage;
            }
        } else {
            $percentages = array_fill(0, count($totalVector), 0);
        }

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $executives,
                'series' => $carteraBySourceAndExecutive,
                'totales' => $totalVector,
                'percentages' => $percentages,
                'total' => $total
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getIncommingCarteraSurveyedUntraceableByDepartment(){ 

        $carteraByDeparment = CarteraTemp::where('mes', 'Marzo')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();

        $carteraByDeparmentSurveyed = CarteraTemp::where('mes', 'Marzo')
            ->where('estatus_contactado', 'CONTACTADO')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();

        $carteraByDeparmentInconsistency = CarteraTemp::where('mes', 'Marzo')
            ->where('queja_ticket', 'Inconsistencia CRM')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();

        $departments = CarteraTemp::where('mes', 'Marzo')->whereNotNull('departamento')->distinct()->pluck('departamento')->toArray();


        $categories = ['product', 'Entrantes', 'Encuestados', 'Ilocalizables'];


        $carteraDeparment = [];
        
        foreach ($departments as $department) {
            $carteraDeparment[$department] = 0;
        }

        foreach ($carteraByDeparment as $lead) {
            $departamento = $lead->departamento;
            $totalCartera = $lead->total_cartera_contacted;
        
            $index = array_search($departamento, $departments);
        
            $carteraDeparment[$departamento] += $totalCartera;
        }

        $carteraDeparment = array_values($carteraDeparment);



        $carteraDepartmentSurveyed = [];

        foreach ($departments as $department) {
            $carteraDepartmentSurveyed[$department] = 0;
        }
        
        foreach ($carteraByDeparmentSurveyed as $lead) {
            $departamento = $lead->departamento;
            $totalCartera = $lead->total_cartera_contacted;
        
            $index = array_search($departamento, $departments);
        
            $carteraDepartmentSurveyed[$departamento] += $totalCartera;
        }

        $carteraDepartmentSurveyed = array_values($carteraDepartmentSurveyed);



        $carteraDeparmentInconsistency = [];

        foreach ($departments as $department) {
            $carteraDeparmentInconsistency[$department] = 0;
        }
        
        foreach ($carteraByDeparmentInconsistency as $lead) {
            $departamento = $lead->departamento;
            $totalCartera = $lead->total_cartera_contacted;
        
            $index = array_search($departamento, $departments);
        
            $carteraDeparmentInconsistency[$departamento] += $totalCartera;
        }

        $carteraDeparmentInconsistency = array_values($carteraDeparmentInconsistency);



        $percentagesSurveyed = [];

        foreach ($carteraDepartmentSurveyed as $i => $encuestado) {
            if ($carteraDeparment[$i] != 0) {
                $porcentaje = round(($carteraDepartmentSurveyed[$i] / $carteraDeparment[$i]) * 100, 2);

                $percentagesSurveyed[] = $porcentaje;
            } else {
                $percentagesSurveyed[] = 0;
            }
        }



        $percentagesInconsistency = [];

        foreach ($carteraDeparmentInconsistency as $i => $inconsistencia) {
            if ($carteraDeparment[$i] != 0) {
                $porcentaje = round(($carteraDeparmentInconsistency[$i] / $carteraDeparment[$i]) * 100, 2);

                $percentagesInconsistency[] = $porcentaje;
            } else {
                $percentagesInconsistency[] = 0;
            }
        }

        $completo = [$departments, $carteraDeparment, $carteraDepartmentSurveyed, $carteraDeparmentInconsistency];

        $matrizTranspuesta = $this->transpose($completo);


        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $categories,
                'carteraDeparment' => $carteraDeparment,
                'carteraDepartmentSurveyed' => $carteraDepartmentSurveyed,
                'carteraDeparmentInconsistency' => $carteraDeparmentInconsistency,
                'percentagesSurveyed' => $percentagesSurveyed,
                'percentagesInconsistency' => $percentagesInconsistency,
                'completo' => $matrizTranspuesta
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getCarteraUntraceableByExecutive(){

        $carteraWrong = CarteraTemp::where('mes', 'Marzo')
            ->where('motivo_no_encuesta', '# Equivocado')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraUnavailable = CarteraTemp::where('mes', 'Marzo')
            ->where('motivo_no_encuesta', '# No disponible')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraNon_existent = CarteraTemp::where('mes', 'Marzo')
            ->where('motivo_no_encuesta', '# No existe')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraVoicemail = CarteraTemp::where('mes', 'Marzo')
            ->where('motivo_no_encuesta', 'Buzon directo')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $executives = CarteraTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();


        $carteraExecutivesWrong = [];
        
        foreach ($executives as $executive) {
            $carteraExecutivesWrong[$executive] = 0;
        }

        foreach ($carteraWrong as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalCartera = $cartera->total_cartera_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $carteraExecutivesWrong[$ejecutivo] += $totalCartera;
        }

        $carteraExecutivesWrong = array_values($carteraExecutivesWrong);



        $carteraExecutivesUnavailable = [];
        
        foreach ($executives as $executive) {
            $carteraExecutivesUnavailable[$executive] = 0;
        }

        foreach ($carteraUnavailable as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalCartera = $cartera->total_cartera_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $carteraExecutivesUnavailable[$ejecutivo] += $totalCartera;
        }

        $carteraExecutivesUnavailable = array_values($carteraExecutivesUnavailable);



        $carteraExecutivesNon_existent = [];
        
        foreach ($executives as $executive) {
            $carteraExecutivesNon_existent[$executive] = 0;
        }

        foreach ($carteraNon_existent as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalCartera = $cartera->total_cartera_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $carteraExecutivesNon_existent[$ejecutivo] += $totalCartera;
        }

        $carteraExecutivesNon_existent = array_values($carteraExecutivesNon_existent);


        $carteraExecutivesVoicemail = [];
        
        foreach ($executives as $executive) {
            $carteraExecutivesVoicemail[$executive] = 0;
        }

        foreach ($carteraVoicemail as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalCartera = $cartera->total_cartera_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $carteraExecutivesVoicemail[$ejecutivo] += $totalCartera;
        }

        $carteraExecutivesVoicemail = array_values($carteraExecutivesVoicemail);

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $executives,
                'carteraExecutivesWrong' => $carteraExecutivesWrong,
                'carteraExecutivesUnavailable' => $carteraExecutivesUnavailable,
                'carteraExecutivesNon_existent' => $carteraExecutivesNon_existent,
                'carteraExecutivesVoicemail' => $carteraExecutivesVoicemail,
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getInconsistenciesByDepartment(){ 

        $leadsByDeparment = LeadTemp::where('mes', 'Marzo')
            ->whereNotNull('departamento')
            ->selectRaw('departamento, COUNT(*) as total_leads_contacted')
            ->groupBy('departamento')
            ->get();
        
        $carteraByDeparment = CarteraTemp::where('mes', 'Marzo')
            ->whereNotNull('departamento')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();


        $leadsByDeparmentSurveyed = LeadTemp::where('mes', 'Marzo')
            ->where('estatus_experiencia', 'CONTACTADO')
            ->selectRaw('departamento, COUNT(*) as total_leads_contacted')
            ->groupBy('departamento')
            ->get();

        $carteraByDeparmentSurveyed = CarteraTemp::where('mes', 'Marzo')
            ->where('estatus_contactado', 'CONTACTADO')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();


        $leadsByDeparmentInconsistency = LeadTemp::where('mes', 'Marzo')
            ->where('queja_ticket', 'Inconsistencia CRM')
            ->selectRaw('departamento, COUNT(*) as total_leads_contacted')
            ->groupBy('departamento')
            ->get();

        $carteraByDeparmentInconsistency = CarteraTemp::where('mes', 'Marzo')
            ->where('queja_ticket', 'Inconsistencia CRM')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();


        $departmentsCartera = CarteraTemp::where('mes', 'Marzo')->whereNotNull('departamento')->distinct()->pluck('departamento')->toArray();

        $departmentsLeads = LeadTemp::where('mes', 'Marzo')->whereNotNull('departamento')->distinct()->pluck('departamento')->toArray();

        $departments = array_merge($departmentsCartera, $departmentsLeads);

        $departments = array_unique($departments);

        $departments = array_values($departments);

        $categories = ['product', 'Entrantes', 'Encuestados', 'Inconsistencia CRM'];


        $propspectsDeparment = [];
        
        foreach ($departments as $department) {
            $propspectsDeparment[$department] = 0;
        }
        
        foreach ($leadsByDeparment as $lead) {
            $departamento = $lead->departamento;
            $totalLeads = $lead->total_leads_contacted;

            $propspectsDeparment[$departamento] += $totalLeads;
        }

        foreach ($carteraByDeparment as $cartera) {
            $departamento = $cartera->departamento;
            $totalCartera = $cartera->total_cartera_contacted;

            $propspectsDeparment[$departamento] += $totalCartera;
        }

        $propspectsDeparment = array_values($propspectsDeparment);


        $propspectsDepartmentSurveyed = [];

        foreach ($departments as $department) {
            $propspectsDepartmentSurveyed[$department] = 0;
        }

        foreach ($leadsByDeparmentSurveyed as $lead) {
            $departamento = $lead->departamento;
            $totalLeads= $lead->total_leads_contacted;
                
            $propspectsDepartmentSurveyed[$departamento] += $totalLeads;
        }

        foreach ($carteraByDeparmentSurveyed as $cartera) {
            $departamento = $cartera->departamento;
            $totalCartera = $cartera->total_cartera_contacted;
                
            $propspectsDepartmentSurveyed[$departamento] += $totalCartera;
        }

        $propspectsDepartmentSurveyed = array_values($propspectsDepartmentSurveyed);


        $prospectsDeparmentInconsistency = [];

        foreach ($departments as $department) {
            $prospectsDeparmentInconsistency[$department] = 0;
        }

        foreach ($leadsByDeparmentInconsistency as $lead) {
            $departamento = $lead->departamento;
            $totalLeads = $lead->total_leads_contacted;
            
            $prospectsDeparmentInconsistency[$departamento] += $totalLeads;
        }
        
        foreach ($carteraByDeparmentInconsistency as $cartera) {
            $departamento = $cartera->departamento;
            $totalCartera = $cartera->total_cartera_contacted;
                
            $prospectsDeparmentInconsistency[$departamento] += $totalCartera;
        }

        $prospectsDeparmentInconsistency = array_values($prospectsDeparmentInconsistency);



        $percentagesSurveyed = [];

        foreach ($propspectsDepartmentSurveyed as $i => $encuestado) {
            if ($propspectsDeparment[$i] != 0) {
                $porcentaje = round(($propspectsDepartmentSurveyed[$i] / $propspectsDeparment[$i]) * 100, 2);

                $percentagesSurveyed[] = $porcentaje;
            } else {
                $percentagesSurveyed[] = 0;
            }
        }

        $percentagesInconsistency = [];

        foreach ($prospectsDeparmentInconsistency as $i => $inconsistencia) {
            if ($propspectsDeparment[$i] != 0) {
                $porcentaje = round(($prospectsDeparmentInconsistency[$i] / $propspectsDeparment[$i]) * 100, 2);

                $percentagesInconsistency[] = $porcentaje;
            } else {
                $percentagesInconsistency[] = 0;
            }
        }

        $completo = [$departments, $propspectsDeparment, $propspectsDepartmentSurveyed, $prospectsDeparmentInconsistency];

        $matrizTranspuesta = $this->transpose($completo);


        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $categories,
                'propspectsDeparment' => $propspectsDeparment,
                'propspectsDepartmentSurveyed' => $propspectsDepartmentSurveyed,
                'prospectsDeparmentInconsistency' => $prospectsDeparmentInconsistency,
                'percentagesSurveyed' => $percentagesSurveyed,
                'percentagesInconsistency' => $percentagesInconsistency,
                'completo' => $matrizTranspuesta
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getInconsistenciesByExecutive(){

        $leadIncidence = LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Actividad pendiente en CRM')
            ->selectRaw('ejecutivo, COUNT(*) as total_lead_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraIncidence = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Actividad pendiente en CRM')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();



        $leadWrongData = LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Datos erroneos')
            ->selectRaw('ejecutivo, COUNT(*) as total_lead_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraWrongData = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Datos erroneos')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();



        $leadNotRequestReports = LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No solicito informes')
            ->selectRaw('ejecutivo, COUNT(*) as total_lead_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraNotRequestReports = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No solicito informes')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();

            
        $leadNoActivityCRM = LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Sin actividad a futuro en CRM')
            ->selectRaw('ejecutivo, COUNT(*) as total_lead_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraNoActivityCRM = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Sin actividad a futuro en CRM')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();


        $leadNoComentaryCRM = LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Sin comentarios en CRM')
            ->selectRaw('ejecutivo, COUNT(*) as total_lead_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraNoComentaryCRM = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Sin comentarios en CRM')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();


        $executivesCartera = CarteraTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();

        $executivesLeads = LeadTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();


        $executives = array_merge($executivesCartera, $executivesLeads);

        $executives = array_unique($executives);


        $executives = array_values($executives);

        $executivesIncidences = [];
        
        foreach ($executives as $executive) {
            $executivesIncidences[$executive] = 0;
        }

        foreach ($leadIncidence as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLead = $lead->total_lead_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesIncidences[$ejecutivo] += $totalLead;
        }

        foreach ($carteraIncidence as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalLead = $cartera->total_cartera_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesIncidences[$ejecutivo] += $totalLead;
        }

        $executivesIncidences = array_values($executivesIncidences);



        $executivesWrongData= [];
        
        foreach ($executives as $executive) {
            $executivesWrongData[$executive] = 0;
        }

        foreach ($leadWrongData as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLead = $lead->total_lead_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesWrongData[$ejecutivo] += $totalLead;
        }

        foreach ($carteraWrongData as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalLead = $cartera->total_cartera_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesWrongData[$ejecutivo] += $totalLead;
        }

        $executivesWrongData = array_values($executivesWrongData);  #falta poner los demás valores



        $executivesNotRequestReports = [];

        foreach ($executives as $executive) {
            $executivesNotRequestReports[$executive] = 0;
        }

        foreach ($leadNotRequestReports as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLead = $lead->total_lead_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesNotRequestReports[$ejecutivo] += $totalLead;
        }

        foreach ($carteraNotRequestReports as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalLead = $cartera->total_cartera_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesNotRequestReports[$ejecutivo] += $totalLead;
        }

        $executivesNotRequestReports = array_values($executivesNotRequestReports);



        $executivesNoActivityCRM = [];

        foreach ($executives as $executive) {
            $executivesNoActivityCRM[$executive] = 0;
        }

        foreach ($leadNoActivityCRM as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLead = $lead->total_lead_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesNoActivityCRM[$ejecutivo] += $totalLead;
        }

        foreach ($carteraNoActivityCRM as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalLead = $cartera->total_cartera_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesNoActivityCRM[$ejecutivo] += $totalLead;
        }

        $executivesNoActivityCRM = array_values($executivesNoActivityCRM);



        $executivesNoComentaryCRM = [];

        foreach ($executives as $executive) {
            $executivesNoComentaryCRM[$executive] = 0;
        }

        foreach ($leadNoComentaryCRM as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLead = $lead->total_lead_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesNoComentaryCRM[$ejecutivo] += $totalLead;
        }

        foreach ($carteraNoComentaryCRM as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalLead = $cartera->total_cartera_contacted;
        
            $index = array_search($ejecutivo, $executives);
        
            $executivesNoComentaryCRM[$ejecutivo] += $totalLead;
        }

        $executivesNoComentaryCRM = array_values($executivesNoComentaryCRM);

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $executives,
                'executivesIncidences' => $executivesIncidences,
                'executivesWrongData' => $executivesWrongData,
                'executivesNotRequestReports' => $executivesNotRequestReports,
                'executivesNoActivityCRM' => $executivesNoActivityCRM,
                'executivesNoComentaryCRM' => $executivesNoComentaryCRM
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getComplainsByDepartment(){

        $leadsByDeparmentNoContact= LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No fue contactado')
            ->selectRaw('departamento, COUNT(*) as total_leads_contacted')
            ->groupBy('departamento')
            ->get();

        $carteraByDeparmentNoContact = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No fue contactado')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();



        $leadsByDeparmentNoFollowUp= LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Ejecutivo no brindo seguimiento')
            ->selectRaw('departamento, COUNT(*) as total_leads_contacted')
            ->groupBy('departamento')
            ->get();

        $carteraByDeparmentNoFollowUp = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Ejecutivo no brindo seguimiento')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();    



        $leadsByDeparmentBadAttention= LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Mala atencion del ejectivo')
            ->selectRaw('departamento, COUNT(*) as total_leads_contacted')
            ->groupBy('departamento')
            ->get();

        $carteraByDeparmentBadAttention = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Mala atencion del ejectivo')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get(); 



        $leadsByDeparmentContactBadSchedule= LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No se comunico en el horario acordado')
            ->selectRaw('departamento, COUNT(*) as total_leads_contacted')
            ->groupBy('departamento')
            ->get();

        $carteraByDeparmentContactBadSchedule = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No se comunico en el horario acordado')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();
        


        $leadsByDeparmentChangeExecutive= LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Solicita cambio de ejecutivo')
            ->selectRaw('departamento, COUNT(*) as total_leads_contacted')
            ->groupBy('departamento')
            ->get();

        $carteraByDeparmentChangeExecutive = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Solicita cambio de ejecutivo')
            ->selectRaw('departamento, COUNT(*) as total_cartera_contacted')
            ->groupBy('departamento')
            ->get();


        $departmentsCartera = CarteraTemp::where('mes', 'Marzo')->whereNotNull('departamento')->distinct()->pluck('departamento')->toArray();

        $departmentsLeads = LeadTemp::where('mes', 'Marzo')->whereNotNull('departamento')->distinct()->pluck('departamento')->toArray();

        $departments = array_merge($departmentsCartera, $departmentsLeads);

        $departments = array_unique($departments);

        $departments = array_values($departments);


        $prospectsDeparmentNoContact = [];
        
        foreach ($departments as $department) {
            $prospectsDeparmentNoContact[$department] = 0;
        }
        
        foreach ($leadsByDeparmentNoContact as $lead) {
            $departamento = $lead->departamento;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsDeparmentNoContact[$departamento] += $totalLeads;
        }

        foreach ($carteraByDeparmentNoContact as $cartera) {
            $departamento = $cartera->departamento;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsDeparmentNoContact[$departamento] += $totalCartera;
        }

        $prospectsDeparmentNoContact = array_values($prospectsDeparmentNoContact);


        $prospectsDeparmentNoFollowUp = [];
        
        foreach ($departments as $department) {
            $prospectsDeparmentNoFollowUp[$department] = 0;
        }
        
        foreach ($leadsByDeparmentNoFollowUp as $lead) {
            $departamento = $lead->departamento;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsDeparmentNoFollowUp[$departamento] += $totalLeads;
        }

        foreach ($carteraByDeparmentNoFollowUp as $cartera) {
            $departamento = $cartera->departamento;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsDeparmentNoFollowUp[$departamento] += $totalCartera;
        }

        $prospectsDeparmentNoFollowUp = array_values($prospectsDeparmentNoFollowUp);


        $prospectsDeparmentBadAttention = [];
        
        foreach ($departments as $department) {
            $prospectsDeparmentBadAttention[$department] = 0;
        }
        
        foreach ($leadsByDeparmentBadAttention as $lead) {
            $departamento = $lead->departamento;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsDeparmentBadAttention[$departamento] += $totalLeads;
        }

        foreach ($carteraByDeparmentBadAttention as $cartera) {
            $departamento = $cartera->departamento;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsDeparmentBadAttention[$departamento] += $totalCartera;
        }

        $prospectsDeparmentBadAttention = array_values($prospectsDeparmentBadAttention);



        $prospectsDeparmentContactBadSchedule = [];
        
        foreach ($departments as $department) {
            $prospectsDeparmentContactBadSchedule[$department] = 0;
        }
        
        foreach ($leadsByDeparmentContactBadSchedule as $lead) {
            $departamento = $lead->departamento;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsDeparmentContactBadSchedule[$departamento] += $totalLeads;
        }

        foreach ($carteraByDeparmentContactBadSchedule as $cartera) {
            $departamento = $cartera->departamento;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsDeparmentContactBadSchedule[$departamento] += $totalCartera;
        }

        $prospectsDeparmentContactBadSchedule = array_values($prospectsDeparmentContactBadSchedule);



        $prospectsDeparmentChangeExecutive = [];
        
        foreach ($departments as $department) {
            $prospectsDeparmentChangeExecutive[$department] = 0;
        }
        
        foreach ($leadsByDeparmentChangeExecutive as $lead) {
            $departamento = $lead->departamento;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsDeparmentChangeExecutive[$departamento] += $totalLeads;
        }

        foreach ($carteraByDeparmentChangeExecutive as $cartera) {
            $departamento = $cartera->departamento;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsDeparmentChangeExecutive[$departamento] += $totalCartera;
        }

        $prospectsDeparmentChangeExecutive = array_values($prospectsDeparmentChangeExecutive);


        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $departments,
                'prospectsDeparmentNoContact' => $prospectsDeparmentNoContact,
                'prospectsDeparmentNoFollowUp' => $prospectsDeparmentNoFollowUp,
                'prospectsDeparmentBadAttention' => $prospectsDeparmentBadAttention,
                'prospectsDeparmentContactBadSchedule' => $prospectsDeparmentContactBadSchedule,
                'prospectsDeparmentChangeExecutive' => $prospectsDeparmentChangeExecutive

            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getComplainsByExecutive(){

        $leadsByExecutiveNoContact = LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No fue contactado')
            ->selectRaw('ejecutivo, COUNT(*) as total_leads_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveNoContact = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No fue contactado')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();



        $leadsByExecutiveNoFollowUp = LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Ejecutivo no brindo seguimiento')
            ->selectRaw('ejecutivo, COUNT(*) as total_leads_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveNoFollowUp = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Ejecutivo no brindo seguimiento')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();    



        $leadsByExecutiveBadAttention= LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Mala atencion del ejectivo')
            ->selectRaw('ejecutivo, COUNT(*) as total_leads_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveBadAttention = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Mala atencion del ejectivo')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get(); 



        $leadsByExecutiveContactBadSchedule = LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No se comunico en el horario acordado')
            ->selectRaw('ejecutivo, COUNT(*) as total_leads_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveContactBadSchedule = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'No se comunico en el horario acordado')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();
        


        $leadsByExecutiveChangeExecutive = LeadTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Solicita cambio de ejecutivo')
            ->selectRaw('ejecutivo, COUNT(*) as total_leads_contacted')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveChangeExecutive = CarteraTemp::where('mes', 'Marzo')
            ->where('incidencia', 'Solicita cambio de ejecutivo')
            ->selectRaw('ejecutivo, COUNT(*) as total_cartera_contacted')
            ->groupBy('ejecutivo')
            ->get();


        $executivesCartera = CarteraTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();

        $executivesLeads = LeadTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();

        $executives = array_merge($executivesCartera, $executivesLeads);

        $executives = array_unique($executives);

        $executives = array_values($executives);



        $prospectsExecutiveNoContact = [];
        
        foreach ($executives as $executive) {
            $prospectsExecutiveNoContact[$executive] = 0;
        }
        
        foreach ($leadsByExecutiveNoContact as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsExecutiveNoContact[$ejecutivo] += $totalLeads;
        }

        foreach ($carteraByExecutiveNoContact as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsExecutiveNoContact[$ejecutivo] += $totalCartera;
        }

        $prospectsExecutiveNoContact = array_values($prospectsExecutiveNoContact);


        $prospectsExecutiveNoFollowUp = [];
        
        foreach ($executives as $executive) {
            $prospectsExecutiveNoFollowUp[$executive] = 0;
        }
        
        foreach ($leadsByExecutiveNoFollowUp as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsExecutiveNoFollowUp[$ejecutivo] += $totalLeads;
        }

        foreach ($carteraByExecutiveNoFollowUp as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsExecutiveNoFollowUp[$ejecutivo] += $totalCartera;
        }

        $prospectsExecutiveNoFollowUp = array_values($prospectsExecutiveNoFollowUp);


        $prospectsExecutiveBadAttention = [];
        
        foreach ($executives as $executive) {
            $prospectsExecutiveBadAttention[$executive] = 0;
        }
        
        foreach ($leadsByExecutiveBadAttention as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsExecutiveBadAttention[$ejecutivo] += $totalLeads;
        }

        foreach ($carteraByExecutiveBadAttention as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsExecutiveBadAttention[$ejecutivo] += $totalCartera;
        }

        $prospectsExecutiveBadAttention = array_values($prospectsExecutiveBadAttention);



        $prospectsExecutiveContactBadSchedule = [];
        
        foreach ($executives as $executive) {
            $prospectsExecutiveContactBadSchedule[$executive] = 0;
        }
        
        foreach ($leadsByExecutiveBadAttention as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsExecutiveContactBadSchedule[$ejecutivo] += $totalLeads;
        }

        foreach ($carteraByExecutiveBadAttention as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsExecutiveContactBadSchedule[$ejecutivo] += $totalCartera;
        }

        $prospectsExecutiveContactBadSchedule = array_values($prospectsExecutiveContactBadSchedule);



        $prospectsExecutiveChangeExecutive = [];
        
        foreach ($executives as $executive) {
            $prospectsExecutiveChangeExecutive[$executive] = 0;
        }
        
        foreach ($leadsByExecutiveBadAttention as $lead) {
            $ejecutivo = $lead->ejecutivo;
            $totalLeads = $lead->total_leads_contacted;

            $prospectsExecutiveChangeExecutive[$ejecutivo] += $totalLeads;
        }

        foreach ($carteraByExecutiveBadAttention as $cartera) {
            $ejecutivo = $cartera->ejecutivo;
            $totalCartera = $cartera->total_cartera_contacted;

            $prospectsExecutiveChangeExecutive[$ejecutivo] += $totalCartera;
        }

        $prospectsExecutiveChangeExecutive = array_values($prospectsExecutiveChangeExecutive);


        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $executives,
                'prospectsExecutiveNoContact' => $prospectsExecutiveNoContact,
                'prospectsExecutiveNoFollowUp' => $prospectsExecutiveNoFollowUp,
                'prospectsExecutiveBadAttention' => $prospectsExecutiveBadAttention,
                'prospectsExecutiveContactBadSchedule' => $prospectsExecutiveContactBadSchedule,
                'prospectsExecutiveChangeExecutive' => $prospectsExecutiveChangeExecutive
            ],
        ];

        return response()->json($response, $response['code']);

    }

    public function getDetailByExecutive(){

        $leadsByExecutiveAssigned = LeadTemp::where('mes', 'Marzo')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $leadsByExecutiveSurveyed = LeadTemp::where('mes', 'Marzo') #calcular porcentaje encuestados
            ->where('estatus_experiencia', 'CONTACTADO')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $leadsByExecutiveSatisfied = LeadTemp::where('mes', 'Marzo') #calcular porcentaje csi
            ->where('calificacion_csi', '5')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $leadsByExecutiveInconsistency = LeadTemp::where('mes', 'Marzo')
            ->where('queja_ticket', 'Inconsistencia CRM')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $leadsByExecutiveComplaint = LeadTemp::where('mes', 'Marzo')
            ->whereNotNull('queja_ticket')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get(); 

        $leadsByExecutiveInfoRequest = LeadTemp::where('mes', 'Marzo')
            ->where('queja_ticket','Solicitud de info/contacto')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get(); 

        $carteraByExecutiveIncomming = CarteraTemp::where('mes', 'Marzo')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveSurveyed = CarteraTemp::where('mes', 'Marzo') #calcular porcentaje encuestados
            ->where('estatus_contactado', 'CONTACTADO')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveSatisfied = CarteraTemp::where('mes', 'Marzo') #calcular porcentaje csi
            ->where('calificacion_csi', '5')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveInconsistency = CarteraTemp::where('mes', 'Marzo')
            ->where('queja_ticket', 'Inconsistencia CRM')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveUntraceable = CarteraTemp::where('mes', 'Marzo')
            ->where('estatus_contactado','Ilocalizable')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        
        $carteraByExecutiveComplaint = CarteraTemp::where('mes', 'Marzo')
            ->whereNotNull('queja_ticket')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $carteraByExecutiveInfoRequest = CarteraTemp::where('mes', 'Marzo')
            ->where('queja_ticket','Solicitud de info/contacto')
            ->selectRaw('ejecutivo, COUNT(*) as total')
            ->groupBy('ejecutivo')
            ->get();

        $executivesCartera = CarteraTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();

        $executivesLeads = LeadTemp::where('mes', 'Marzo')->whereNotNull('ejecutivo')->distinct()->pluck('ejecutivo')->toArray();

        $executives = array_merge($executivesCartera, $executivesLeads);

        $executives = array_unique($executives);

        $executives = array_values($executives);



        $executivesData = [];

        foreach ($executives as $executive) {
            $leadsAssigned = $this->findDataByExecutive($leadsByExecutiveAssigned, $executive);
            $leadsSurveyed = $this->findDataByExecutive($leadsByExecutiveSurveyed, $executive);
            $leadsSatisfied = $this->findDataByExecutive($leadsByExecutiveSatisfied, $executive);
            $walletIncoming = $this->findDataByExecutive($carteraByExecutiveIncomming, $executive);
            $walletSurveyed = $this->findDataByExecutive($carteraByExecutiveSurveyed, $executive);
            $walletSatisfied = $this->findDataByExecutive($carteraByExecutiveSatisfied, $executive);

            $executiveData = [
                'name' => $executive,
                'leadsAssigned' => $leadsAssigned,
                'leadsSurveyed' => $leadsSurveyed,
                'leadsSurveyedPercentage' =>  $leadsAssigned < 0 ? ($leadsSurveyed/$leadsAssigned) : 0,
                'leadsSatisfied' => $leadsSatisfied,
                'percentageSatisfied' => $leadsSurveyed < 0 ? ($leadsSatisfied/$leadsSurveyed) : 0,
                'leadsInconsCRM' => $this->findDataByExecutive($leadsByExecutiveInconsistency, $executive),
                'leadsComplaints' => $this->findDataByExecutive($leadsByExecutiveComplaint, $executive),
                'leadsRequests' => $this->findDataByExecutive($leadsByExecutiveInfoRequest, $executive),
                'walletIncoming' => $walletIncoming,
                'walletSurveyed' => $walletSurveyed,
                'walletSurveyedPercentage' => $walletSurveyed < 0 ? ($walletIncoming/$walletSurveyed) : 0,
                'walletSatisfied' => $walletSatisfied,
                'csiWallet' => $walletSurveyed < 0 ? ($walletSatisfied/$walletSurveyed) : 0,
                'walletIncons' => $this->findDataByExecutive($carteraByExecutiveInconsistency, $executive),
                'untraceables' => $this->findDataByExecutive($carteraByExecutiveUntraceable, $executive),
                'walletComplaints' => $this->findDataByExecutive($carteraByExecutiveComplaint, $executive),
                'walletRequests' => $this->findDataByExecutive($carteraByExecutiveInfoRequest, $executive),
                'leads' => true,
                'wallet' => false,
                'activeTab' => 'leads'
            ];

            $executivesData[] = $executiveData;
        }

        $executivesData = array_values($executivesData);

        $response = [
            'code' => 200,
            'status' => 'success',
            'data' => [
                'categories' => $executives,
                'detailsExecutives' => $executivesData
            ],
        ];

        return response()->json($response, $response['code']);

    }


    function transpose($array)
    {
        return array_map(null, ...$array);
    }
    
    function findDataByExecutive($data, $executive) {
        foreach ($data as $item) {
            if ($item->ejecutivo === $executive) {
                return $item->total;
            }
        }
        return 0;
    }
}
