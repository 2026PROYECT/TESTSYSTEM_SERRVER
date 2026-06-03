<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\QRCode as QRCodeModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class QRCodeController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $qrCodes = QRCodeModel::latest()->paginate(15);
            return response()->json([
                'success' => true,
                'data' => $qrCodes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'description' => 'nullable|string',
                'size' => 'nullable|integer|min:100|max:1000',
                'color' => 'nullable|string',
                'background' => 'nullable|string',
            ]);
            
            $data = $validated;
            
            $qrCode = QRCodeModel::create($data);
            
            return response()->json([
                'success' => true,
                'data' => $qrCode
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $qrCode = QRCodeModel::findOrFail($id);
            $qrCode->increment('scans');
            return response()->json([
                'success' => true,
                'data' => $qrCode
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $qrCode = QRCodeModel::findOrFail($id);
            
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'description' => 'nullable|string',
                'color' => 'nullable|string',
                'background' => 'nullable|string',
            ]);
            
            $qrCode->update($validated);
            
            return response()->json([
                'success' => true,
                'data' => $qrCode
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $qrCode = QRCodeModel::findOrFail($id);
            $qrCode->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'QR eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function generate(Request $request)
    {
        try {
            $content = $request->input('content', 'https://testsystem.es.ht');
            $size = $request->input('size', 300);
            
            $qrImage = QrCode::size($size)->generate($content);
            
            return response($qrImage)
                ->header('Content-Type', 'image/svg+xml')
                ->header('Cache-Control', 'no-cache');
                
        } catch (\Exception $e) {
            Log::error('QR Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}