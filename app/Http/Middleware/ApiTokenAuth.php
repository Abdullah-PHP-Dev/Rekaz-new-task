<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization', '');
        
        if (!preg_match('/^Bearer\s+(.+)$/', $header, $m)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $token = $m[1];
        if (!$token || $token !== env('SIMPLEDRIVE_API_TOKEN')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return $next($request);
    }
}
