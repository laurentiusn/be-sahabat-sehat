<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tips;
use App\Models\GambarTips;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TipsController extends Controller
{
    /**
     * Display a listing of the resource.
     * Endpoint: GET /api/v1/admin/tips
     */
    public function index(Request $request)
    {
        // Eager load menggunakan nama relasi 'gambarTips' dan 'indikator'
        $query = Tips::with(['gambarTips', 'indikator']);

        // Filter by id_indikator
        if ($request->has('id_indikator')) {
            $query->where('id_indikator', $request->query('id_indikator'));
        }

        $tips = $query->latest()->get();

        return response()->json([
            'statusCode' => 200,
            'message'    => 'List Data Tips Kesehatan',
            'data'       => $tips
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Endpoint: POST /api/v1/admin/tips
     * Body (form-data):
     * - id_indikator: UUID
     * - konten: Text
     * - images[]: File (Multiple)
     */
    public function store(Request $request)
    {
        // 1. Validasi
        $validator = Validator::make($request->all(), [
            'id_indikator' => 'required|exists:indikator,id',
            'konten'       => 'required|string',
            'images'       => 'required|array|max:5', // Maksimal 5 gambar
            'images.*'     => 'required|image|mimes:jpeg,png,jpg|max:2048', // Validasi tiap file
        ]);

        if ($validator->fails()) {
            return response()->json(['statusCode' => 422, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // 2. Simpan Data Tips Utama
            $tips = Tips::create([
                'id_indikator' => $request->id_indikator,
                'konten'       => $request->konten,
            ]);

            // 3. Simpan Gambar (Looping Array File)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    // Upload ke folder public/tips-images
                    $path = $file->store('tips-images', 'public');

                    GambarTips::create([
                        'id_tips'     => $tips->id,
                        'path_gambar' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'statusCode' => 201,
                'message'    => 'Tips berhasil dibuat',
                'data'       => $tips->load('gambarTips')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['statusCode' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     * Endpoint: GET /api/v1/admin/tips/{id}
     */
    public function show(string $id)
    {
        $tips = Tips::with(['gambarTips', 'indikator'])->find($id);

        if (!$tips) {
            return response()->json(['statusCode' => 404, 'message' => 'Tips tidak ditemukan'], 404);
        }

        return response()->json([
            'statusCode' => 200,
            'message'    => 'Detail Tips',
            'data'       => $tips
        ]);
    }

    /**
     * Update the specified resource in storage.
     * Endpoint: POST /api/v1/admin/tips/{id}?_method=PUT
     * Body (form-data):
     * - konten: Text (Optional)
     * - deleted_image_ids[]: UUID (Optional - hapus gambar lama)
     * - new_images[]: File (Optional - tambah gambar baru)
     */
    public function update(Request $request, string $id)
    {
        $tips = Tips::find($id);
        if (!$tips) return response()->json(['statusCode' => 404, 'message' => 'Tips tidak ditemukan'], 404);

        $validator = Validator::make($request->all(), [
            'id_indikator'        => 'sometimes|required|exists:indikator,id',
            'konten'              => 'sometimes|required|string',
            
            // Array ID dari tabel gambar_tips yang ingin dihapus
            'deleted_image_ids'   => 'nullable|array',
            'deleted_image_ids.*' => 'exists:gambar_tips,id',

            // Array file gambar baru untuk ditambahkan
            'new_images'          => 'nullable|array',
            'new_images.*'        => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['statusCode' => 422, 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Update Konten & Indikator
            $tips->update($request->only(['konten', 'id_indikator']));

            // 2. Hapus Gambar Lama (Jika ada request)
            if ($request->has('deleted_image_ids')) {
                $imagesToDelete = GambarTips::whereIn('id', $request->deleted_image_ids)
                                            ->where('id_tips', $tips->id)
                                            ->get();
                
                foreach ($imagesToDelete as $img) {
                    // Hapus file fisik
                    if (Storage::disk('public')->exists($img->path_gambar)) {
                        Storage::disk('public')->delete($img->path_gambar);
                    }
                    // Hapus record database
                    $img->delete();
                }
            }

            // 3. Tambah Gambar Baru (Jika ada upload baru)
            if ($request->hasFile('new_images')) {
                foreach ($request->file('new_images') as $file) {
                    $path = $file->store('tips-images', 'public');
                    
                    GambarTips::create([
                        'id_tips'     => $tips->id,
                        'path_gambar' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Tips berhasil diperbarui',
                'data'       => $tips->fresh(['gambarTips'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['statusCode' => 500, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Endpoint: DELETE /api/v1/admin/tips/{id}
     */
    public function destroy(string $id)
    {
        $tips = Tips::with('gambarTips')->find($id);

        if (!$tips) {
            return response()->json(['statusCode' => 404, 'message' => 'Tips tidak ditemukan'], 404);
        }

        try {
            // 1. Hapus File Fisik Gambar
            foreach ($tips->gambarTips as $gambar) {
                if (Storage::disk('public')->exists($gambar->path_gambar)) {
                    Storage::disk('public')->delete($gambar->path_gambar);
                }
            }

            // 2. Hapus Data (Cascade di migration akan otomatis menghapus row di gambar_tips)
            $tips->delete();

            return response()->json([
                'statusCode' => 200,
                'message'    => 'Tips dan seluruh gambarnya berhasil dihapus permanen'
            ]);

        } catch (\Exception $e) {
            return response()->json(['statusCode' => 500, 'message' => $e->getMessage()], 500);
        }
    }
}