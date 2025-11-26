<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateIndikatorRequest;
use App\Http\Requests\Api\UpdateIndikatorRequest;
use App\Models\Indikator;
use Illuminate\Http\Request;

class IndikatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Indikator::select(['type', 'status', 'range', 'color_hex'])
                ->orderBy('type', 'asc')        
                ->get();

        return response()->json([
            'statusCode' => 200,
            'message' => 'Data Indikators Berhasil',
            'data' => $query,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateIndikatorRequest $request)
    {
        $validatedData = $request->validated();

        $indikator = Indikator::create($validatedData);

        return response()->json([
            'statusCode'=> 201,
            'message'   => 'Indikator berhasil dibuat',
            'data'      => $indikator,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateIndikatorRequest $request, string $id)
    {
        $indikator = Indikator::findOrFail($id);

        $validatedData = $request->validated();

        $indikator->update($validatedData);

        // 4. Return Response
        return response()->json([
            'statusCode' => 200,
            'message'    => 'Data Indikator Berhasil Diperbarui',
            'data'       => $indikator
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $indikator = Indikator::findOrFail($id);
        $indikator->delete();

        return response()->json([
            'statusCode' => 200,
            'message'    => 'Indikator berhasil dihapus'
        ], 200);
    }
}
