<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateIndikatorRequest;
use App\Http\Requests\Api\UpdateIndikatorRequest;
use App\Models\Indikator;
use App\Models\Tips;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndikatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Indikator::select(['id','type', 'status', 'range', 'color_hex'])
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

        DB::beginTransaction();
        try {
            // 1. Simpan Indikator
            $indikator = Indikator::create($validatedData);

            // 2. Simpan Tips Default (Karena relasi wajib ada di database/logic)
            // Kita isi konten dengan '-' karena kolom tidak boleh null
            Tips::create([
                'id_indikator' => $indikator->id,
                'konten'       => '-', 
            ]);

            DB::commit();

            return response()->json([
                'statusCode'=> 201,
                'message'   => 'Indikator dan Tips default berhasil dibuat',
                'data'      => $indikator,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'statusCode' => 500,
                'message'    => 'Gagal menyimpan data',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $indikator = Indikator::findOrFail($id);

        return response()->json([
            'statusCode' => 200,
            'message'    => 'Detail Indikator',
            'data'       => $indikator
        ], 200);
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
