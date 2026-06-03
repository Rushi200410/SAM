<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyFaceAttendanceToken
{
    public function handle(Request $request, Closure $next)
    {
        $expectedToken = (string) config('services.attendance_face.token', '');

        if ($expectedToken === '') {
            return response()->json([
                'message' => 'Face attendance token is not configured.',
            ], 503);
        }

        $providedToken = (string) $request->header('X-ATTENDANCE-TOKEN', '');

        if ($providedToken === '' && $request->bearerToken()) {
            $providedToken = (string) $request->bearerToken();
        }

        if (!hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'message' => 'Unauthorized request.',
            ], 401);
        }

        return $next($request);
    }
}
