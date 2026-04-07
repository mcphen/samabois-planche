<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index()
    {
        return Inertia::render('Suppliers/Index');
    }

    public function getSuppliers(Request $request)
    {
        $suppliers = Supplier::all();
        return response()->json(['data'=>$suppliers]);
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $request['slug_name'] = Str::slug($request['name']);
        //dd($request->all());
        //Supplier::create($request->all());

        $supplier = Supplier::create([
            'name' => $request->name,
            'slug_name' => Str::slug($request->name),
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return response()->json(['message' => 'Fournisseur ajouté avec succès.','supplier'=>$supplier]);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(['message' => 'Fournisseur supprimé avec succès.']);
    }
}
