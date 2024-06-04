<?php

namespace App\Imports;

use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\CarteraTemp;

class CreateCarteraTempImport implements WithHeadingRow, ToCollection
{
    public function collection(Collection $rows)
    {
        if( Session::has('response') ){
            $response = array();
            $response = Session::get('response');                             
        }else{
            $response = array(
                'cartera' => array(),
            );
        }

        foreach ($rows as $index => $row ) 
        {
            $carteraTemp = new CarteraTemp();
            $carteraTemp->fill($row->toArray());
            $carteraTemp->save();

            array_push($response['cartera'], "Fila " . ($index + 1) . ": Cartera creado con exito" );
              
        }

        Session::flash('response', $response ); 
    }
}
