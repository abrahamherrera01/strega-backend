<?php

namespace App\Imports;

use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Order;
use App\Models\Survey;
use App\Models\Vehicle;
use App\Models\Incidence;

class CreateSurveysToSaleChevroletImport implements WithHeadingRow, ToCollection
{
    public function collection(Collection $rows)
    {
        // Variables
        $response = array();

        if( Session::has('response') ){
            $response = Session::get('response');                             
        }else{
            $response = array(                
                'exists' => array(),
                'surveys' => array(),
                'incidences' => array(),
                'errors' => array()
            );
        } 

        foreach ($rows as $index => $row ) 
        {          
            if( isset($row['vin']) ){      
                $vehicle = Vehicle::where('vin', $row['vin'])->first(); 
                if( is_object($vehicle) && !is_null($vehicle) ){
                    $order = Order::where('vehicle_id', $vehicle->id)->where('order_type', 'Sale')->first();
                    if( is_object($order) && !is_null($order)){           
                        $survey = $order->survey()->whereNotNull('nps')->first();
                        if( !is_object($survey) && is_null($survey) ){     
                            if( isset($row['csi']) && isset($row['nps']) ){
                                $survey = new Survey();                        
                                $survey->csi = $row['csi'];                       
                                $survey->nps = $row['nps'];                           
                                $survey->comments = $row['comentarios'];
                                $survey->satisfaction = $row['satisfaccion'];
                                $survey->order_id = $order->id;
                                if($survey->save()){
                                    array_push($response['surveys'], "Fila " . ($index + 2) . ": encuesta creada con exito" );
                                    $incidence = Incidence::where('order_id', $order->id)->first();                                    
                                    if( !is_object($incidence) && is_null($incidence) && isset($row['incidencia']) ){
                                        $incidence = new Incidence();
                                        $incidence->name = $row['incidencia'];
                                        $incidence->order_id = $order->id;
                                        if($incidence->save()){
                                            array_push($response['incidences'], "Fila " . ($index + 2) . ": incidencia creada con exito" );
                                        }
                                    }
                                }  
                            }else{
                                array_push($response['errors'], "Fila " . ($index + 2) . " : las columnas csi, nps son necesarias");
                            }                                                        
                        }else{       
                            array_push($response['exists'], "Fila " . ($index + 2) . ": la orden con id " . $order->id . " ya cuenta con una encuesta");
                        }  
                    } 
                }else{
                    array_push($response['errors'], "Fila " . ($index + 2) . ": no existe ningún vehículo con el vin " . $row['vin']);
                }                                             
            }else{
                array_push($response['errors'], "Fila " . ($index + 2) . ": la columna 'vin' es necesaria");
            }              
        }
        Session::flash('response', $response ); 
    }
}
