<?php

namespace App\Imports;

use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\ServicioTemp;

class CreateServicioTempImport implements WithHeadingRow, ToCollection
{
    public function collection(Collection $rows)
    {
        if ( Session::has('response') ) {
            $response = array();
            $response = Session::get('response');
        }else {
            $response = array(
                'servicio' => array(),
            );
        }

        foreach ($rows as $index => $row) {
            $servicioTemp = new ServicioTemp();
            $servicioTemp->fill($row->toArray());
            $servicioTemp->save();

            array_push($response['servicio'], "Fila " . ($index + 1) . ": Servicio creado con exito" );

        }

        Session::flash('response', $response);
    }
}