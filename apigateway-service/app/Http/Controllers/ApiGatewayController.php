<?php

namespace App\Http\Controllers;

use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class ApiGatewayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function products(Request $request)
    {
        try{
            $headers = $request->headers->all();
            $response = Http::withHeaders($headers)
            ->retry(3,500)
            ->get(env('PRODUCTS_SERVICE_URL').'/api/v1/products');
            return $response->json();
        }catch(Exception $ex){
            return response()->json(['error' => $ex->getMessage()],$ex->getCode());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function login(Request $request)
    {
        try{
            $response = Http::retry(3,500)
            ->timeout(60000)->post(env('AUTH_SERVICE_URL').'/api/v1/auth/login',$request->all());
            return $response->json();
        }catch(Exception $ex){
            return response()->json(['error' => $ex->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function checkToken(Request $request)
    {
        $token = $request->header('Authorization');
        // Verificar si el token está presente
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            // Eliminar el prefijo 'Bearer ' del token si está presente
            $token = str_replace('Bearer ', '', $token);

       
            // Decodifica el token JWT
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            // Devuelve el contenido del token si no ha expirado
            return response()->json(['data' => $decoded]);
        } catch (ExpiredException $e) {
            // Maneja el caso cuando el token ha expirado
            return response()->json(['error' => 'Token has expired'], 401);
        } catch (\Exception $e) {
            // Maneja cualquier otro error
            return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 401);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
