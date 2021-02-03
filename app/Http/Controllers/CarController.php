<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Http\Requests;
use App\Car;
// Para subir las imágeness
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class CarController extends Controller
{
    public function index() {
        $cars = Car::all()->load('user');
        return response()->json(array(
            'cars' => $cars,
            'status' => 'success'
        ));
        
    }

    public function getOffers() {
        $carsOffers = Car::all()->where('status', 'true')->values();
        return response()->json(array(
            'carsOffers' => $carsOffers,
            'status' => 'success'
        ));
    }

    public function show($id) {
        $car = Car::find($id);
        if (is_object($car)) {
            $car = Car::find($id)->load('user');
            return response()->json(array('car' => $car, 'status' => 'success'), 200);
        } else {
            return response()->json(array('message' => 'El coche no existe', 'status' => 'error'), 200);
        }
        
        
    }

    public function store(Request $request) {
        $hash = $request->header('Authorization', null);
        $jwtAuth = new JwtAuth();
        $checkToken = $jwtAuth->checkToken($hash);

        if($checkToken) {
            // Recoger datos por POST
            $json = $request->input('json', null);
            $json = str_replace('&quot;', '"', $json);
            $params = json_decode(stripslashes($json));
            $params_array = json_decode($json, true);
            
            // Conseguir el usuario identificado
            $user = $jwtAuth->checkToken($hash, true);

            // Validación
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'description' => 'required',
                'price' => 'required',
                'marca' => 'required',
                'motor' => 'required',
                'caballos' => 'required',
                'cambio' => 'required',
                'status' => 'required'
            ]);

            if($validate->fails()) {
                return response()->json($validate->errors(), 400);
            }

            // Guardar el coche
            $car = new Car();
            $car->user_id = $user->sub;
            $car->title = $params->title;
            $car->description = $params->description;
            $car->status = $params->status;
            $car->price = $params->price;
            $car->marca = $params->marca;
            $car->motor = $params->motor;
            $car->caballos = $params->caballos;
            $car->cambio = $params->cambio;

            // Subida de la imagen
            $image = $request->file('image');
            if ($image) {
                $image_path = $image->getClientOriginalName();
                \Storage::disk('images')->put($image_path, \File::get($image));
                $car->image = $image_path;
            }
            /*
            //check file
            if ($request->hasFile('image'))
            {
                $file      = $request->file('image');
                $filename  = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $picture   = date('His').'-'.$filename;
                //move image to public/img folder
                $file->move(public_path('img'), $picture);
                return response()->json(["message" => "Image Uploaded Succesfully"]);
            } 
            else
            {
                return response()->json(["message" => "Select image first."]);
            }*/

            // $car->image = null;
    
            $car->save();

            $data = array(
                'car' => $car,
                'request' => $image,
                'status' => 'success',
                'code' => 200
            );
            
        } else {
            // Devolver error
            $data = array(
                'car' => 'Login incorrecto',
                'status' => 'error',
                'code' => 400
            );
        }

        return response()->json($data, 200);
    }

    public function update($id, Request $request) {
        $hash = $request->header('Authorization', null);
        $jwtAuth = new JwtAuth();
        $checkToken = $jwtAuth->checkToken($hash);

        if($checkToken) {
            // Recoger parámetros por POST
            $json = $request->input('json', null);
            $json = str_replace('&quot;', '"', $json);
            $params = json_decode(stripslashes($json));
            $params_array = json_decode($json, true);

            // Validación
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'description' => 'required',
                'price' => 'required',
                'marca' => 'required',
                'motor' => 'required',
                'caballos' => 'required',
                'cambio' => 'required',
                'modelo' => 'modelo',
                'status' => 'required'
            ]);
            // 'image' => 'mimes:jpeg,bmp,png'

            if($validate->fails()) {
                return response()->json($validate->errors(), 400);
            }

            // Actualizar el coche
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['updated_at']);
            unset($params_array['user']);
            $car = Car::where('id', $id)->update($params_array);

            $data = array(
                'car' => $params,
                'status' => 'success',
                'code' => 200
            );

        } else {
            // Devolver error
            $data = array(
                'message' => 'Login incorrecto',
                'status' => 'error',
                'code' => 400
            );
        }

        return response()->json($data, 200);
    }

    public function destroy($id, Request $request) {
        $hash = $request->header('Authorization', null);
        $jwtAuth = new JwtAuth();
        $checkToken = $jwtAuth->checkToken($hash);

        if($checkToken) {
            // Comprobar que existe el registro
            $car = Car::find($id);

            // Borrarlo
            $car->delete();

            // Devolverlo
            $data = array(
                'car' => $car,
                'status' => 'success',
                'code' => 200
            );

        } else {
            // Devolver error
            $data = array(
                'message' => 'Login incorrecto',
                'status' => 'error',
                'code' => 400
            );
        }

        return response()->json($data, 200);
    }

}
