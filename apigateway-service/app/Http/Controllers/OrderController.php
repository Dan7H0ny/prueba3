<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class OrderController extends Controller
{
    protected $db;
    protected $collection;
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->db = new Client(env('MONGO_URI'));
        $this->collection = $this->db->orders_services_db->orders;
    }
    public function index()
    {
        try{
            $products = $this->collection->find()->toArray();
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
            'items' => 'required|array',
            'items.*.product_id' => 'required|string',
            'items.*.name' => 'required|string|max:100',
            'items.*.description' => 'required|string|max:1000',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.category' => 'required|string|max:100',
            'items.*.available' => 'required|boolean',
            'items.*.ingredients' => 'required|array',
            'items.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|string|in:pending,completed,canceled'
        ]);
        
        $updatedProducts = [];

        try{

            $token = $request->header('Authorization');
            $token = str_replace("Bearer ",'',$token);


            foreach ($valData['items'] as $pro) {
                
                $inventoryResponse = Http::withToken($token)
                ->timeout(600)->get(env('INVENTORY_SERVICE_URL').'/api/v1/products/'.$pro['product_id']);

                if($inventoryResponse->failed() || !$inventoryResponse->json()){
                    return response()->json(['error' => 'Producto no encontrado'],Response::HTTP_NOT_FOUND);
                }

                $product = $inventoryResponse->json()["producto"];
                if($product['quantity'] < $pro['quantity']){
                    return response()->json(['error' => 'No hay suficiente stock'],Response::HTTP_NOT_FOUND);
                }


                $updatedProducts [] = [
                    'product_id' => $pro['product_id'],
                    'new_quantity' => $product['quantity'] - $pro['quantity']
                ];
            }

            foreach ($updatedProducts as $productUpdate) {
                $updatedResponse = Http::withToken($token)
                ->timeout(600)->put(env('INVENTORY_SERVICE_URL').'/api/v1/products/'.$pro['product_id'],[
                    'quantity' => $productUpdate['new_quantity']
                ]);

                if($updatedResponse->failed()){
                    return response()->json(['error' => 'Error al actualizar el inventario'],Response::HTTP_NOT_FOUND);
                }
            }

            
            $data = [
                'customer_name'=> $valData['customer_name'],
                'items'=> $valData['items'],
                'total_price'=> $valData['total_price'],
                'status'=> $valData['status'],
                'created_at'=> new UTCDateTime(),
                'updated_at'=> new UTCDateTime()
            ];
            $orderResult = $this->collection->insertOne($data);
            $data['id'] = $orderResult->getInsertedId();
            $data['_id'] = $orderResult->getInsertedId();

            $token = $request->header('Authorization');
            $token = str_replace("Bearer ",'',$token);

            $emailResponse = Http::withToken($token)
            ->timeout(600)->post(env('EMAIL_SERVICE_URL').'/api/v1/emails',[
                'from'=>'no-reply@gmail.com',
                'to'=>'novaho2095@rowplant.com',
                'subject'=>'Confirmación de Nuevo pedido #'.$data['_id'],
                'content'=>'Gracias por su compra, su pedido ha sido recibido con éxito',
                'order'=>$data
            ]);

            if($emailResponse->failed() || !$emailResponse->json()){
                return response()->json(['error' => 'Error enviando el email'],Response::HTTP_INTERNAL_SERVER_ERROR);
            }

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
            $order = $this->collection->findOne(['_id'=> new ObjectId($id)]);
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $valData = $request->validate([
            'customer_name'=>'sometimes|required|string|max:100',
            'items'=>'sometimes|required|array',
            'total_price'=>'sometimes|required|numeric|min:0',
            'status'=>'sometimes|required|string|in:pending,completed,canceled'
        ]);
        try{
            $updateData = array_filter($valData);

            if(empty($updateData)){
                return response()->json(['error' => 'No se encontraron datos para actualizar'],Response::HTTP_BAD_REQUEST);
            }
            
            $product = $this->collection->updateOne(
                ['_id' => new ObjectId($id)],
                ['$set' => $updateData]
            );

            if($product->getMatchedCount() === 0){
                return response()->json(['error' => 'No encontrado'],Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'message' => 'Órden actualizada con éxito'
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
            $product = $this->collection->deleteOne(['_id'=> new ObjectId($id)]);
            if($product->getDeletedCount() === 0){
                return response()->json(['error' => 'No encontrado'],Response::HTTP_NOT_FOUND);
            }
            return response()->json([
                'message' => 'Órden eliminada con éxito',
                'producto'=> $product
            ],Response::HTTP_OK);
        }catch(Exception $ex){
            return response()->json(['error' => $ex->getMessage()],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}
