<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\veiculos;
use DateTime;
use App\Http\Requests\validateVeiculo;


use Illuminate\Support\Facades\DB;
use Doctrine\DBAL\Driver\PDOConnection;

class VeiculoController extends Controller
{

    private $veiculo;
    private $data;
    public function __construct(veiculos $veiculo)  //(patio $patio) e o mesmo que $patio = new patio;
    {
        $this->veiculo = $veiculo;
        $this->setData($veiculo->all());
    }

    public function setData($data)
    {
        $this->data=$data;
    }

    public function getData()
    {
        return $this->data;
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // carregar veiculo mostrando o tipo com join  
        $data = $this->getData();
        $data = json_encode($data);
        $data = (array)$data;

        return view('relatorioPatio',compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(validateVeiculo $request)
    {
        date_default_timezone_set('America/Sao_Paulo');
        $timestamp = date("Y-m-d H:i:s");

        $validated = $request->validated(); // valida campos com base na classe tipo request de validaçao do laravel  php artisan make:request StoreBlogPost
        $data = $request->all();
        $data['horaEntrada'] = $timestamp;
        $this->veiculo->create($data);

        return redirect()->route('home');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function relVeiculos(Request $request)
    {
        $request = $request->all();
        //dd($request['dtFinal']);  "2020-04-18T00:00" Retirar este T entre a data e a hora
        $request['dtInicial'] = str_replace('T', ' ', $request['dtInicial'] );
        $request['dtFinal'] = str_replace('T', ' ', $request['dtFinal'] );
        
        $data = $this->veiculo->whereRaw('horaEntrada >= ? and horaSaida <= ? ',[$request['dtInicial'],$request['dtFinal']])
                                                   
                                                            ->join('tipos', 'tipos.id', '=', 'veiculos.tipoId')
                                                            ->get();                           
                                                            
                                                            
        $data['valorSoma'] = $data->sum('valorTotal');

        return view('relatorioPatio',compact('data'));

    }

    public function show($id)
    {
        return 'carrega veiculo!!!!';
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
       // retornar formulario preffechido com os dados do banco de dados
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        date_default_timezone_set('America/Sao_Paulo');
        $dateAtual = date("Y-m-d H:i:s");

        $dataVeiculo = $this->veiculo->find($id);
    
        $d1     =   new DateTime( $dataVeiculo['horaEntrada'] );

        $d2     =   new DateTime( $dateAtual );

        //Calcula a diferença entre as horas
        $diff   =   (array)$d1->diff($d2, true);   

        $qtdHoras = $diff["h"];
        $qtdMinutos = $diff["i"];

        $valorTotal = 0;
        $diaria = false;

        // calculo 4 primeiras Horas 12 reais
        if( $qtdHoras>0 && $qtdHoras<2 )
        {
            $valorTotal += 12;
        }
        else if( $qtdHoras>1 && $qtdHoras<3 )
        {
            $valorTotal += 24;
        }
        else if( $qtdHoras>2 && $qtdHoras<4 )
        {
            $valorTotal += 36;
        }
        else if( $qtdHoras>3 && $qtdHoras<5 )
        {
            $valorTotal += 48;
        }
        else if( $qtdHoras>=5 )
        {
            // Acima de 4 horas vira diaria 30 reais
            $valorTotal +=60;
            $diaria = true;
        }
        
        // calculo minutos se ainda nao for mais que 4 horas pois vira diaria
        if($diaria === false)
        {
            if( $qtdMinutos>=0 && $qtdMinutos<=15 )
            {
                $valorTotal += 3;
            }
        
            else if( $qtdMinutos>=16 && $qtdMinutos<=30 )
            {
                $valorTotal += 6;
            }
    
            else if( $qtdMinutos>=31 && $qtdMinutos<=60 )
            {
                $valorTotal += 9;
            }
        }

        //echo "Valor Total $valorTotal <br> $qtdHoras Horas de permanencia  e $qtdMinutos minutos . ";
        //exit;
        
        $veiuloFind = $this->veiculo->find($id);

        $update = $veiuloFind->update(
                                [
                                    'horaSaida'=>$dateAtual,
                                    'valorTotal'=>$valorTotal

                                ]);
        return redirect()->route('home',$id);
    
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        
    }
}
