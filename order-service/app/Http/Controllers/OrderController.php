<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{

    public function index()
    {
        try{
            $products = Order::get();
            return response()->json($products,Response::HTTP_OK);
        }catch(Exception $ex){
            return response()->json(['error' => $ex->getMessage()],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $valData = $request->validate([
            'customer_name' => 'required|string|max:100',
            'product_id' => 'required|string',
            'product_name' => 'required|string|max:100',
            'quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0'
        ]);

        $updatedProducts = [];

        try{

            $token = $request->header('Authorization');
            $token = str_replace("Bearer ",'',$token);

                error_log($token);


                $inventoryResponse = Http::withToken($token)
                ->timeout(600)->get(env('INVENTORY_SERVICE_URL').'/api/v1/products/'.$valData['product_id']);

                if($inventoryResponse->failed() || !$inventoryResponse->json()){
                    return response()->json(['error' => 'Producto no encontrado'],Response::HTTP_NOT_FOUND);
                }

                $product = $inventoryResponse->json()["producto"];
                if($product['quantity'] < $valData['quantity']){
                    return response()->json(['error' => 'No hay suficiente stock'],Response::HTTP_NOT_FOUND);
                }


                    $new_quantity = $product['quantity'] - $valData['quantity'];

                try {
                    $updatedResponse = Http::withToken($token)
                    ->timeout(600)->put(env('INVENTORY_SERVICE_URL').'/api/v1/products/'.$valData['product_id'],[
                        'quantity' => $new_quantity
                    ]);
                } catch (\Exception $e) {
                    return $e->getMessage();
                }




            $data = [
                'customer_name'=> $valData['customer_name'],
                'product_id'=> $valData['product_id'],
                'product_name'=> $valData['product_name'],
                'total_price'=> $valData['total_price']
            ];
            $orderResult = Order::create($data);

            return response()->json([
                'message' => 'Orden enviada con éxito',
                'order' => $data
            ],Response::HTTP_CREATED);
        }catch(Exception $ex){
            return response()->json(['error' => $ex->getMessage()],Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $order = Order::find($id);
            if(!$order){
                return response()->json(['error' => 'No encontrada'],Response::HTTP_NOT_FOUND);
            }
            return response()->json([
                'message' => 'Órden encontrada',
                'producto'=> $order
            ],Response::HTTP_OK);
        }catch(Exception $ex){
            return response()->json(['error' => $ex->getMessage()],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $product = Order::find($id);
            if(!$product){
                return response()->json(['error' => 'No encontrado'],Response::HTTP_NOT_FOUND);
            }
            $product->delete($id);
            return response()->json([
                'message' => 'Órden eliminada con éxito',
                'producto'=> $product
            ],Response::HTTP_OK);

        }catch(Exception $ex){
            return response()->json(['error' => $ex->getMessage()],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}
