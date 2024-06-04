<?php

namespace App\Imports;

use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Order;
use App\Models\Survey;
use App\Models\Incidence;
use App\Models\Category;
use App\Models\Area;

class CreateSurveysToAfterSaleChevroletImport implements WithHeadingRow, ToCollection
{
    public function collection(Collection $rows)
    {        
        // Ejemplo de row
        // Illuminate\Support\Collection {
        //     [
        //       "orden" => "D00191665"
        //       "fecha" => "02/01/2024"
        //       "nombre" => "MARIA DEL ROSARIO AGUILAR ROSETE"
        //       "estatus_crm" => null
        //       "correo" => null
        //       "telefono_1" => 2228446557
        //       "telefono_2" => null
        //       "asesor" => 10
        //       "modelo" => "AVEO"
        //       "serie" => "3G1TA5AF1FL241001"
        //       "satisfaccion" => "Completamente Satisfecho"
        //       "calificacion_csi" => 5
        //       "recomendacion" => 10
        //       "eficiencia" => 10
        //       "trabajo" => 10
        //       "incidencia" => null
        //       "comentarios" => null
        //       "intentos" => 1
        //       "estatus" => "Contactado"
        //       "motivo_no_contactado" => null
        //       "correo_electronico_correcto" => null
        //       "medio_de_contacto" => "WHATSAPP"
        //       "whatsapp" => "SI"
        //       "tipificacion_incidencia" => null
        //     ]            
        // }

        // Variables
        $response = array();

        if( Session::has('response') ){
            $response = Session::get('response');                             
        }else{
            $response = array(                
                'exists' => array(),
                'surveys' => array(),
                'incidences' => array(),
                'categories' => array(),
                'areas' => array(),
                'errors' => array()
            );
        } 

        foreach ($rows as $index => $row ) 
        {          
            if( isset($row['orden']) ){                
                $order = Order::where('id_order_bp', $row['orden'])->first();
                if( is_object($order) && !is_null($order)){           
                    $survey = $order->survey()->whereNotNull('recomendation')->first();
                    if( !is_object($survey) && is_null($survey) ){     
                        if( isset($row['csi']) && is_numeric($row['csi']) &&
                            isset($row['satisfaccion']) &&
                            isset($row['recomendacion']) && is_numeric($row['recomendacion']) && 
                            isset($row['eficiencia']) && is_numeric($row['eficiencia']) && 
                            isset($row['asesor']) && is_numeric($row['asesor']) && 
                            isset($row['trabajo']) && is_numeric($row['trabajo']) ){
                            $survey = new Survey();                        
                            $survey->csi = $row['csi'];                       
                            $survey->satisfaction = $row['satisfaccion'];
                            $survey->recomendation = $row['recomendacion'];
                            $survey->efficiency = $row['eficiencia'];
                            $survey->advisor = $row['asesor'];
                            $survey->job = $row['trabajo']; 
                            $survey->comments = $row['comentarios'];                           
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
                                        // Creación de categorias en caso de existir en csv
                                        if( isset($row['tipo_de_queja_1']) ){
                                            $category = Category::where('name', trim($row['tipo_de_queja_1']))->first();
                                            if( !is_null( $category ) && is_object( $category ) ){
                                                $incidence->categories()->syncWithoutDetaching($category->id);
                                            }else{
                                                $category = new Category();
                                                $category->name = trim($row['tipo_de_queja_1']);
                                                if( $category->save() ){
                                                    $incidence->categories()->syncWithoutDetaching($category->id);
                                                    array_push($response['categories'], "Fila " . ($index + 2) . ": categoria creada con exito" );
                                                }
                                            }                                            
                                        }
                                        if( isset($row['tipo_de_queja_2']) ){
                                            $category = Category::where('name', trim($row['tipo_de_queja_2']))->first();
                                            if( !is_null( $category ) && is_object( $category ) ){
                                                $incidence->categories()->syncWithoutDetaching($category->id);
                                            }else{
                                                $category = new Category();
                                                $category->name = trim($row['tipo_de_queja_2']);
                                                if( $category->save() ){
                                                    $incidence->categories()->syncWithoutDetaching($category->id);
                                                    array_push($response['categories'], "Fila " . ($index + 2) . ": categoria creada con exito" );
                                                }
                                            }                                            
                                        }
                                        if( isset($row['tipo_de_queja_3']) ){
                                            $category = Category::where('name', trim($row['tipo_de_queja_3']))->first();
                                            if( !is_null( $category ) && is_object( $category ) ){
                                                $incidence->categories()->syncWithoutDetaching($category->id);
                                            }else{
                                                $category = new Category();
                                                $category->name = trim($row['tipo_de_queja_3']);
                                                if( $category->save() ){
                                                    $incidence->categories()->syncWithoutDetaching($category->id);
                                                    array_push($response['categories'], "Fila " . ($index + 2) . ": categoria creada con exito" );
                                                }
                                            }                                            
                                        }

                                        // Creación de áreas en caso de existir en csv
                                        if( isset($row['area_1']) ){
                                            $area = Area::where('name', trim($row['area_1']))->first();
                                            if( !is_null( $area ) && is_object( $area ) ){
                                                $incidence->areas()->syncWithoutDetaching($area->id);
                                            }else{
                                                $area = new Area();
                                                $area->name = trim($row['area_1']);
                                                if( $area->save() ){
                                                    $incidence->areas()->syncWithoutDetaching($area->id);
                                                    array_push($response['areas'], "Fila " . ($index + 2) . ": area creada con exito" );
                                                }
                                            }                                            
                                        }
                                        if( isset($row['area_2']) ){
                                            $area = Area::where('name', trim($row['area_2']))->first();
                                            if( !is_null( $area ) && is_object( $area ) ){
                                                $incidence->areas()->syncWithoutDetaching($area->id);
                                            }else{
                                                $area = new Area();
                                                $area->name = trim($row['area_2']);
                                                if( $area->save() ){
                                                    $incidence->areas()->syncWithoutDetaching($area->id);
                                                    array_push($response['areas'], "Fila " . ($index + 2) . ": area creada con exito" );
                                                }
                                            }                                            
                                        }
                                    }
                                }
                            }  
                        }else{
                            array_push($response['errors'], "Fila " . ($index + 2) . ": las columnas csi, satisfaccion, recomendacion, eficiencia, asesor, trabajo son necesarias y deben ser enteros");
                        }                                                        
                    }else{       
                        array_push($response['exists'], "Fila " . ($index + 2) . ": la orden con id " . $row['orden'] . " ya cuenta con una encuesta");
                    }  
                }                     
            }else{
                array_push($response['errors'], "Fila " . ($index + 2) . ": la columna 'orden' es necesaria");
            }              
        }
        Session::flash('response', $response ); 
    }
}
