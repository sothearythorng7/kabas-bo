<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    /**
     * Receive logs from POS client
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'logs' => 'required|array',
            'logs.*.level' => 'required|string|in:debug,info,warn,error,critical',
            'logs.*.message' => 'required|string|max:1000',
            'logs.*.context' => 'nullable|array',
            'logs.*.timestamp' => 'required|string',
            'device_info' => 'nullable|array',
        ]);

        $deviceInfo = $data['device_info'] ?? [];

        foreach ($data['logs'] as $log) {
            $context = array_merge(
                $log['context'] ?? [],
                [
                    'pos_client' => true,
                    'client_timestamp' => $log['timestamp'],
                    'user_agent' => $request->userAgent(),
                    'ip' => $request->ip(),
                    'device' => $deviceInfo,
                ]
            );

            // Store in database for easy viewing
            DB::table('pos_client_logs')->insert([
                'level' => $log['level'],
                'message' => $log['message'],
                'context' => json_encode($context),
                'client_timestamp' => $log['timestamp'],
                'created_at' => now(),
            ]);

            // Also log to Laravel logs for immediate viewing
            $logMethod = $log['level'] === 'warn' ? 'warning' : $log['level'];
            Log::channel('pos')->$logMethod("[POS Client] {$log['message']}", $context);
        }

        return response()->json(['status' => 'ok', 'received' => count($data['logs'])]);
    }

    /**
     * View POS client logs
     */
    public function index(Request $request)
    {
        $logs = DB::table('pos_client_logs')
            ->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 200))
            ->get()
            ->map(function ($log) {
                $log->context = json_decode($log->context, true);
                return $log;
            });

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        return view('pos.logs', compact('logs'));
    }

    /**
     * Clear old logs
     */
    public function clear(Request $request)
    {
        $deleted = DB::table('pos_client_logs')
            ->where('created_at', '<', now()->subDays(7))
            ->delete();

        return response()->json(['status' => 'ok', 'deleted' => $deleted]);
    }
}
