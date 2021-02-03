<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\JwtAuth;
use App\Http\Requests;
use App\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function register(Request $request) {
        $json = $request->input('json', null);
        $json = str_replace('&quot;', '"', $json);
        $params = json_decode($json);
        
        $email = (!is_null($json) && isset($params->email)) ? $params->email : null;
        $name = (!is_null($json) && isset($params->name)) ? $params->name : null;
        $surname = (!is_null($json) && isset($params->surname)) ? $params->surname : null;
        $role = 'ROLE_USER';
        $password = (!is_null($json) && isset($params->password)) ? $params->password : null;

        if(!is_null($email) && !is_null($name) && !is_null($surname)) {
            // Crear el usuario
            $user = new User();
            $user->email = $email;
            $user->name = $name;
            $user->surname = $surname;
            $user->role = $role;

            $pwd = hash('sha256', $password);
            $user->password = $pwd;

            // Comprobar usuario duplicado
            $isset_user = User::select('email')->where('email', '=', $email)->get();

            if(count($isset_user) == 0) {
                // Guardar el usuario
                $user->save();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Usuario creado correctamente'
                );
            } else {
                // No guardarlo porque ya existe
                $data = array(
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Email ya registrado'
                );
            }

        } else {
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'Usuario no creado'
            );
        }

        return response()->json($data, 200);
    }

    public function login(Request $request) {
        $jwtAuth = new JwtAuth();

        // Recibir POST
        $json = $request->input('json', null);
        $json = str_replace('&quot;', '"', $json);
        $params = json_decode($json);

        $email = (!is_null($json) && isset($params->email)) ? $params->email : null;
        $password = (!is_null($json) && isset($params->password)) ? $params->password : null;
        $getToken = (!is_null($json) && isset($params->getToken)) ? $params->getToken : null;

        // Cifrar la password
        $pwd = hash('sha256', $password);

        if(!is_null($email) && !is_null($password) && ($getToken == null ||  $getToken == 'false')) {
            $signup = $jwtAuth->signup($email, $pwd);
        } elseif($getToken != null) {
            $signup = $jwtAuth->signup($email, $pwd, $getToken);
        } else {
            $signup = array(
                'status' => 'error',
                'message' => 'Envía tus datos por post'
            );
        }

        return response()->json($signup, 200);
    }
}
