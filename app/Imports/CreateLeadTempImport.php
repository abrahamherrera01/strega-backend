<?php

namespace App\Imports;

use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\LeadTemp;

class CreateLeadTempImport implements WithHeadingRow, ToCollection
{
    public function collection(Collection $rows)
    {
        if( Session::has('response') ){
            $response = array();
            $response = Session::get('response');                             
        }else{
            $response = array(
                'leads' => array(),
            );
        }

        foreach ($rows as $index => $row ) 
        {
            $leadTemp = new LeadTemp();
            $leadTemp->fill($row->toArray());
            $leadTemp->save();

            array_push($response['leads'], "Fila " . ($index + 1) . ": Lead creado con exito" );
              
        }

        Session::flash('response', $response ); 
    }
}
