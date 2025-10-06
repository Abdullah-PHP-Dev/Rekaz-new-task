<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SimpleDrive\AdapterFactory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class BlobController extends Controller
{ 
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'id' => 'required|string',
            'data' => 'required|string',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => 'Invalid input', 'errors' => $v->errors()], 422);
        }

        $id = $request->input('id');
        $b64 = $request->input('data');

        $binary = base64_decode($b64, true);
        if ($binary === false) {
            return response()->json(['message' => 'Invalid base64 data'], 422);
        }
        
        $adapter = \App\Services\SimpleDrive\AdapterFactory::make();
        
        try {
            $storagePath = $adapter->save($id, $binary);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to store', 'error' => $e->getMessage()], 500);
        }

        $size = strlen($binary);
        $createdAt = Carbon::now('UTC')->toDateTimeString();

        // write metadata to blobs table
        DB::table('blobs')->updateOrInsert(
            ['id' => $id],
            [
                'backend' => config('simpledrive.default'),
                'storage_path' => $storagePath,
                'size' => $size,
                'created_at' => now()->setTimezone('UTC'),
            ]
        );

        return response()->json([
            'id' => $id,
            'data' => $b64,
            'size' => (string)$size,
            'created_at' => Carbon::now('UTC')->toIso8601String(),
        ], 201);
    }

    public function show($id)
    {
        $row = DB::table('blobs')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Not found'], 404);
        }

        // use adapter based on stored backend (so you can switch per-record)
        $backend = $row->backend;
        // temporary override config for factory
        // easiest: instantiate desired adapter directly
        $adapter = match ($backend) {
            'local' => new \App\Services\SimpleDrive\Adapters\LocalAdapter(),
            'db' => new \App\Services\SimpleDrive\Adapters\DatabaseAdapter(),
            's3' => new \App\Services\SimpleDrive\Adapters\S3Adapter(),
            'ftp' => new \App\Services\SimpleDrive\Adapters\FtpAdapter(),
            default => new \App\Services\SimpleDrive\Adapters\LocalAdapter(),
        };

        try {
            $binary = $adapter->get($row->storage_path);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to retrieve', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'id' => $row->id,
            'data' => base64_encode($binary),
            'size' => (string) $row->size,
            'created_at' => \Carbon\Carbon::parse($row->created_at, 'UTC')->toIso8601String(),
        ]);
    }
}
