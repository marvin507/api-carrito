<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\Product;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['orderDetails', 'user'])->orderBy('order_number', 'desc')->get();

        return response()->json($orders);
    }

    public function completeOrder(Request $request)
    {
        $validated = $request->validate([
            'total' => 'required|numeric',
            'items' => 'required|array',
        ]);

        $last_order_number = Order::max('order_number');

        $new_order_number = $last_order_number ? $last_order_number + 1 : 10000001;

        $order = Order::create([
            'user_id' => $request->user()->id,
            'order_number' => $new_order_number,
            'total' => $validated['total'],
        ]);
        
        foreach ($validated['items'] as $item) {

            $order->orderDetails()->create([
                'product_id' => $item['id'],
                'quantity' => $item['cantidad'],
                'price' => $item['price'],
            ]);

            $this->removeStock($item['id'], $item['cantidad']);
        }

        return response()->json([
            'status' => 'ok',
            'message' => 'Orden creada con éxito, numero de orden: '.$new_order_number,
        ], 200);
    }

    public function removeStock($product_id, $quantity)
    {
        $product = Product::find($product_id);

        $product->update([
            'stock' => $product->stock - $quantity,
        ]);
    }

    public function show(String $id)
    {
        $order = Order::with(['orderDetails.product', 'user'])->find($id);

        return response()->json($order);
    }
}
