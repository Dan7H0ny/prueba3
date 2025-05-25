<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        try{
            $products = Product::all();
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

      /*  $valData = $request->validate([
            'name'=>'required|string|max:100',
            'description'=>'required|string|max:1000',
            'price'=>'required|numeric|min:0',
            'category'=>'required|string|max:100',
            'available'=>'required|boolean',
            'ingredients'=>'required|array',
            'quantity'=>'required|integer',
        ]);*/
        try{


            $exists = Product::whereName($request->name)->first();

            if($exists){
                return response()->json(['error'=>'Ya existe el producto con ese nombre'],Response::HTTP_CONFLICT);
            }

            $params = $request->all();
            $data = Product::create($params);


            return response()->json([
                'message' => 'Guardado con éxito',
                'product' => $data
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
            $product = Product::find($id);
            if(!$product){
                return response()->json(['error' => 'No encontrado'],Response::HTTP_NOT_FOUND);
            }
            return response()->json([
                'message' => 'Producto encontrado',
                'producto'=> $product
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
        try{

            $product = Product::find($id);

            if(!$product){
                return response()->json(['error' => 'No encontrado'],Response::HTTP_NOT_FOUND);
            }

            $data = $product->update(['quantity' => $request->quantity]);

            return response()->json([
                'message' => 'Actualizado con éxito'
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
            $product = Product::find($id);
            if(!$product){
                return response()->json(['error' => 'No encontrado'],Response::HTTP_NOT_FOUND);
            }
            $product->delete($id);
            return response()->json([
                'message' => 'Eliminado con éxito',
                'producto'=> $product
            ],Response::HTTP_OK);
        }catch(Exception $ex){
            return response()->json(['error' => $ex->getMessage()],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function searchByName(Request $request)
{
   /* $request->validate([
        'name' => 'required|string|max:100',
    ]);
*/
    try {
        $name = $request->name;
        $products = Product::whereName($name)->get();

        if (empty($products)) {
            return response()->json(['message' => 'No se encontraron productos con ese nombre.'], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'message' => 'Productos encontrados con éxito',
            'products' => $products
        ], Response::HTTP_OK);
    } catch (Exception $ex) {
        return response()->json(['error' => $ex->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
}
