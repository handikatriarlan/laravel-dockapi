<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::latest()->paginate(5);

        return new ProductResource(true, 'Product Data List', $products);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image'         => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title'         => 'required|string',
            'description'   => 'required|string',
            'price'         => 'required|numeric',
            'stock'         => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $image = $request->file('image');
        $image->storeAs('products', $image->hashName());

        $product = Product::create([
            'image'         => $image->hashName(),
            'title'         => $request->title,
            'description'   => $request->description,
            'price'         => $request->price,
            'stock'         => $request->stock,
        ]);

        return new ProductResource(true, 'Product Data Successfully Added!', $product);
    }

    public function show($id)
    {
        $product = Product::find($id);

        return new ProductResource(true, 'Detail Data Product', $product);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'image'         => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title'         => 'string',
            'description'   => 'string',
            'price'         => 'numeric',
            'stock'         => 'numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product = Product::find($id);
        $data = [];

        if ($request->has('title')) {
            $data['title'] = $request->title;
        }
        if ($request->has('description')) {
            $data['description'] = $request->description;
        }
        if ($request->has('price')) {
            $data['price'] = $request->price;
        }
        if ($request->has('stock')) {
            $data['stock'] = $request->stock;
        }

        if ($request->hasFile('image')) {
            Storage::delete('products/' . basename($product->image));

            $image = $request->file('image');
            $image->storeAs('products', $image->hashName());

            $data['image'] = $image->hashName();
        }

        $product->update($data);

        return new ProductResource(true, 'Product Data Successfully Updated!', $product);
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found!'
            ], 404);
        }

        Storage::delete('products/' . basename($product->image));

        $product->delete();

        return new ProductResource(true, 'Product Data Successfully Deleted!', null);
    }
}
