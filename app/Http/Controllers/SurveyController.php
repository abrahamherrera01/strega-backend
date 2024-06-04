<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
// Excel
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CreateSurveysToAfterSaleChevroletImport;
use App\Imports\CreateSurveysToSaleChevroletImport;

class SurveyController extends Controller
{
    public function loadSurveysAfterSale( Request $request ){
        $file = $request->file('file');
        Excel::import(new CreateSurveysToAfterSaleChevroletImport, $file);
        $response = Session::get('response');
        $data = array(
            'code' => 200,
            'status' => 'success',
            'respuesta' => $response
        );
        return response()->json($data, $data['code']);
    }    

    public function loadSurveysSale( Request $request ){
        $file = $request->file('file');
        Excel::import(new CreateSurveysToSaleChevroletImport, $file);
        $response = Session::get('response');
        $data = array(
            'code' => 200,
            'status' => 'success',
            'respuesta' => $response
        );
        return response()->json($data, $data['code']);
    }   
}
