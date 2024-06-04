<?php

namespace App\Imports;

use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\VentaTemp;

class CreateVentaTempImport implements WithHeadingRow, ToCollection
{
    public function collection(Collection $rows)
    {
        if ( Session::has('response') ) {
            $response = array();
            $response = Session::get('response');
        }else {
            $response = array(
                'venta' => array(),
            );
        }

        foreach ($rows as $index => $row)
        {
            $ventaTemp = new VentaTemp();
            $ventaTemp->fill($row->toArray());
            $ventaTemp->save();

            array_push($response['venta'], "Fila " . ($index + 1) . ": Venta creada con exito" );

        }

        Session::flash('response', $response);
    }
}