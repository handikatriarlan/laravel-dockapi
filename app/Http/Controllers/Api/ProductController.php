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

        if ($products->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No products found!',
                'data'    => []
            ], 404);
        }

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

        try {
            $image = $request->file('image');
            $image->storeAs('products', $image->hashName(), 'public');

            $product = Product::create([
                'image'         => $image->hashName(),
                'title'         => $request->title,
                'description'   => $request->description,
                'price'         => $request->price,
                'stock'         => $request->stock,
            ]);

            return new ProductResource(true, 'Product Data Successfully Added!', $product);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found!'
            ], 404);
        }

        return new ProductResource(true, 'Detail Data Product', $product);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'image'         => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title'         => 'nullable|string',
            'description'   => 'nullable|string',
            'price'         => 'nullable|numeric',
            'stock'         => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found!'
            ], 404);
        }

        $data = $request->only(['title', 'description', 'price', 'stock']);

        if ($request->hasFile('image')) {
            $imageName = basename($product->image);

            if ($imageName && Storage::disk('public')->exists("products/{$imageName}")) {
                Storage::disk('public')->delete("products/{$imageName}");
            }

            $image = $request->file('image');
            $imageName = $image->hashName();
            $image->storeAs('products', $imageName, 'public');

            $data['image'] = $imageName;
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

        $imageName = basename($product->image);

        if ($imageName && Storage::disk('public')->exists("products/{$imageName}")) {
            Storage::disk('public')->delete("products/{$imageName}");
        }

        $product->delete();

        return new ProductResource(true, 'Product Data Successfully Deleted!', null);
    }
}
