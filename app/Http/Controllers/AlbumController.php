<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Album;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\File;

class AlbumController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $q_album = Album::select('*')->orderByDesc('created_at');
            return DataTables::of($q_album)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<a class="btn-sm app-btn-danger deleteAlbum" data-id="' . $row->id . '" href="#">Hapus</a>';
                    $btn .= '<a class="btn-sm app-btn-primary editAlbum" data-id="' . $row->id . '" href="#">Edit</a>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $data = [
            'title' => 'Album',
        ];

        return view('content.album', $data);
    }

    public function create()
    {
        return view('form.create-album');
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'judul' => 'required|string',
                'gambar-album' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            $album = new Album();
            $album->judul = $request->input('judul');

            if ($request->hasFile('gambar-album')) {
                $gambar = $request->file('gambar-album');
                $gambar_album = time() . '_album.' . $gambar->getClientOriginalExtension();
                $gambarPath = public_path('images/album');
                $gambar->move($gambarPath, $gambar_album);
                $album->gambar = $gambar_album;
            }

            $album->save();

            return redirect()->route('album.index')->with('success', 'Album berhasil disimpan');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }



    public function edit($id)
    {
        $album = Album::find($id);
        if (!$album) {
            return redirect()->route('album.index')->withErrors(['error' => 'Album tidak ditemukan']);
        }
        return view('form.create-album', compact('album'));
    }

    public function update(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'judul' => 'required|string',
                'gambar-album' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            ]);

            $album = Album::find($id);
            if (!$album) {
                return redirect()->route('album.index')->withErrors(['error' => 'Album tidak ditemukan']);
            }

            $album->judul = $request->input('judul');

            if ($request->hasFile('gambar-album')) {
                // Delete the old image if exists
                if ($album->gambar) {
                    $oldImagePath = public_path('images/album/' . $album->gambar);
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $gambar = $request->file('gambar-album');
                $gambar_album = time() . '_album.' . $gambar->getClientOriginalExtension();
                $gambarPath = public_path('images/album');
                $gambar->move($gambarPath, $gambar_album);
                $album->gambar = $gambar_album;
            }

            $album->save();
            return redirect()->route('album.index')->with('success', 'Album berhasil diperbarui');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $album = Album::find($id);
        if (!$album) {
            return response()->json(['status' => 'error', 'message' => 'Album tidak ditemukan'], 404);
        }

        try {
            DB::transaction(function () use ($album) {
                if ($album->gambar) {
                    $imagePath = public_path('images/album/' . $album->gambar);
                    if (File::exists($imagePath)) {
                        File::delete($imagePath);
                    }
                }
                $album->delete();
            });

            return response()->json(['status' => 'success', 'message' => 'Album berhasil dihapus']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
