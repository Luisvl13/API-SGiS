<?php

namespace App\Http\Controllers\V1\Catalogos;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response as HttpResponse;

use App\Http\Requests;
use \Validator,\Hash, \Response, \DB;
use Illuminate\Support\Facades\Input;

use App\Models\Catalogos\NivelesCones;
use App\Models\Catalogos\Clues;

class NivelConeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parametros = Input::only('q','page','per_page');
        if ($parametros['q']) {
            $data =  NivelesCones::where(function($query) use ($parametros) {
                $query->where('id','LIKE',"%".$parametros['q']."%")
                    ->orWhere('nombre','LIKE',"%".$parametros['q']."%");
            });
        } else {
            $data =  NivelesCones::where("id","!=", "");
        }


        if(isset($parametros['page'])){

            $resultadosPorPagina = isset($parametros["per_page"])? $parametros["per_page"] : 20;
            $data = $data->paginate($resultadosPorPagina);
        } else {
            $data = $data->get();
        }

        return Response::json([ 'data' => $data],200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $datos = Input::json()->all();

        $success = false;
        $errors_main = array();
        DB::beginTransaction();

        try {
            if(array_key_exists('niveles_cones', $datos)) { // Insertar más de un registro
                foreach ($datos['niveles_cones'] as $key => $value) {
                    $validacion = $this->ValidarParametros($key, NULL, $value);
                    if($validacion != ""){
                        array_push($errors_main, $validacion);
                    }

                    $data = new NivelesCones;

                    $data->nombre = $value['nombre'];

                    if ($data->save())
                        $datos = (object) $datos;
                    $this->AgregarDatos($datos, $data);
                    $success = true;
                }

                if(count($errors_main)>0) {
                    return Response::json(['error' => array_collapse($errors_main)], HttpResponse::HTTP_CONFLICT);
                }
            } else {

                $validacion = $this->ValidarParametros("", NULL, $datos);
                if($validacion != ""){
                    return Response::json(['error' => $validacion], HttpResponse::HTTP_CONFLICT);
                }

                $data = new NivelesCones;

                $data->nombre = $datos['nombre'];

                if ($data->save())
                    $datos = (object) $datos;
                $this->AgregarDatos($datos, $data);
                $success = true;
            }
        } catch (\Exception $e){
            return Response::json($e->getMessage(), 500);
        }

        if ($success){
            DB::commit();
            return Response::json(array("status" => 201,"messages" => "Creado","data" => $data), 201);
        } else{
            DB::rollback();
            return Response::json(array("status" => 409,"messages" => "Conflicto"), 409);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = NivelesCones::find($id);

        if(!$data){
            return Response::json(['error' => "No se encuentra el recurso que esta buscando."], HttpResponse::HTTP_NOT_FOUND);
        }

        return Response::json([ 'data' => $data ], HttpResponse::HTTP_OK);
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
        $mensajes = [

            'required'      => "required",
            'unique'        => "unique"
        ];

        $reglas = [
            'nombre'        => 'required',
        ];

        $inputs = Input::only('nombre');

        $v = Validator::make($inputs, $reglas, $mensajes);

        if ($v->fails()) {
            return Response::json(['error' => $v->errors()], HttpResponse::HTTP_CONFLICT);
        }

        try {
            $data = NivelesCones::find($id);
            $data->nombre =  $inputs['nombre'];

            $data->save();
            return Response::json([ 'data' => $data ],200);

        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $data = NivelesCones::destroy($id);
            return Response::json(['data'=>$data],200);
        } catch (Exception $e) {
            return Response::json(['error' => $e->getMessage()], HttpResponse::HTTP_CONFLICT);
        }
    }

    /**
     * Validad los parametros recibidos, Esto no tiene ruta de acceso es un metodo privado del controlador.
     *
     * @param  Request  $request que corresponde a los parametros enviados por el cliente
     *
     * @return Response
     * <code> Respuesta Error json con los errores encontrados </code>
     */
    private function ValidarParametros($key, $id, $request){

        $messages = [
            'required' => 'required',
            'unique' => 'unique'
        ];

        /*
        if($request['nivel_cone']) {
            $nivel_cone = $request['nivel_cone'];
        } else {
            $nivel_cone = NULL;
        }
        */
        $rules = [
            'nombre' => 'required|min:3|max:250|unique:niveles_cones',
        ];

        $v = Validator::make($request, $rules, $messages);

        if ($v->fails()){
            $mensages_validacion = array();
            foreach ($v->errors()->messages() as $indice => $item) { // todos los mensajes de todos los campos
                $msg_validacion = array();
                foreach ($item as $msg) {
                    array_push($msg_validacion, $msg);
                }
                array_push($mensages_validacion, array($indice.''.$key => $msg_validacion));
            }
            return $mensages_validacion;
        }else{
            return ;
        }
    }

    private function AgregarDatos($datos, $data){
        //verificar si existe resguardos, en caso de que exista proceder a guardarlo

        if(property_exists($datos, "clues")){
            //limpiar el arreglo de posibles nullos
            $detalle = array_filter($datos->clues, function($v){return $v !== null;});
            //recorrer cada elemento del arreglo
            foreach ($detalle as $key => $value) {
                //validar que el valor no sea null
                if($value != null){
                    //comprobar si el value es un array, si es convertirlo a object mas facil para manejar.
                    if(is_array($value))
                        $value = (object) $value;

                    //comprobar que el dato que se envio no exista o este borrado, si existe y esta borrado poner en activo nuevamente
                    DB::select("update clues set nivel_cone_id = '$data->id' where clues = '$value->clues' ");
                    //si existe el elemento actualizar
                    $clues = Clues::where("nivel_cone_id", $data->id)->first();
                    //si no existe crear
                    if(!$clues)
                        $clues = new Clues;

                    $clues->nivel_cone_id       =       $data->id;

                    $clues->save();

                }
            }


        }
    }
}