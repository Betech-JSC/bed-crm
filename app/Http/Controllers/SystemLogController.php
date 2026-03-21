<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class SystemLogController extends Controller
{
    /**
     * Available log files.
     */
    private function getLogFiles(): array
    {
        $logPath = storage_path('logs');
        $files = [];

        if (File::isDirectory($logPath)) {
            foreach (File::files($logPath) as $file) {
                if ($file->getExtension() === 'log') {
                    $files[] = [
                        'name' => $file->getFilename(),
                        'path' => $file->getRealPath(),
                        'size' => $this->formatBytes($file->getSize()),
                        'size_bytes' => $file->getSize(),
                        'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    ];
                }
            }
        }

        usort($files, fn ($a, $b) => $b['size_bytes'] <=> $a['size_bytes']);

        return $files;
    }

    /**
     * Show the log viewer page.
     */
    public function index(): Response
    {
        return Inertia::render('SystemLogs/Index', [
            'logFiles' => $this->getLogFiles(),
        ]);
    }

    /**
     * Fetch parsed log entries via AJAX.
     */
    public function fetch(Request $request): JsonResponse
    {
        $file = $request->input('file', 'laravel.log');
        $level = $request->input('level', 'all');
        $search = $request->input('search', '');
        $limit = min((int) $request->input('limit', 100), 500);
        $page = max((int) $request->input('page', 1), 1);

        // Sanitize filename — prevent directory traversal
        $file = basename($file);
        $logPath = storage_path('logs/' . $file);

        if (!File::exists($logPath) || !str_ends_with($logPath, '.log')) {
            return response()->json(['entries' => [], 'total' => 0, 'stats' => []]);
        }

        $entries = $this->parseLogFile($logPath);

        // Stats (before filtering)
        $stats = $this->computeStats($entries);

        // Filter by level
        if ($level !== 'all') {
            $entries = array_values(array_filter($entries, fn($e) => strtolower($e['level']) === strtolower($level)));
        }

        // Filter by search
        if ($search) {
            $searchLower = strtolower($search);
            $entries = array_values(array_filter($entries, fn($e) =>
                str_contains(strtolower($e['message']), $searchLower) ||
                str_contains(strtolower($e['context'] ?? ''), $searchLower)
            ));
        }

        // Reverse so newest first
        $entries = array_reverse($entries);

        // Paginate
        $total = count($entries);
        $offset = ($page - 1) * $limit;
        $entries = array_slice($entries, $offset, $limit);

        return response()->json([
            'entries' => $entries,
            'total' => $total,
            'page' => $page,
            'per_page' => $limit,
            'stats' => $stats,
        ]);
    }

    /**
     * Stream log file content in real-time (tail).
     */
    public function stream(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $file = basename($request->input('file', 'laravel.log'));
        $logPath = storage_path('logs/' . $file);

        return response()->stream(function () use ($logPath) {
            $lastSize = 0;

            if (File::exists($logPath)) {
                // Send last 5KB initially
                $fileSize = File::size($logPath);
                $startPos = max(0, $fileSize - 5120);
                $handle = fopen($logPath, 'r');
                fseek($handle, $startPos);
                $initial = fread($handle, $fileSize - $startPos);
                fclose($handle);

                echo "data: " . json_encode(['type' => 'initial', 'content' => $initial]) . "\n\n";
                ob_flush();
                flush();

                $lastSize = $fileSize;
            }

            // Poll for changes (30 seconds max)
            $endTime = time() + 30;
            while (time() < $endTime) {
                if (connection_aborted()) break;

                clearstatcache(true, $logPath);
                if (File::exists($logPath)) {
                    $currentSize = File::size($logPath);
                    if ($currentSize > $lastSize) {
                        $handle = fopen($logPath, 'r');
                        fseek($handle, $lastSize);
                        $newContent = fread($handle, $currentSize - $lastSize);
                        fclose($handle);

                        echo "data: " . json_encode(['type' => 'append', 'content' => $newContent]) . "\n\n";
                        ob_flush();
                        flush();

                        $lastSize = $currentSize;
                    }
                }
                usleep(500000); // 0.5s
            }

            echo "data: " . json_encode(['type' => 'end']) . "\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Clear a log file.
     */
    public function clear(Request $request): JsonResponse
    {
        $file = basename($request->input('file', 'laravel.log'));
        $logPath = storage_path('logs/' . $file);

        if (File::exists($logPath) && str_ends_with($logPath, '.log')) {
            File::put($logPath, '');
            return response()->json(['success' => true, 'message' => 'Log file cleared.']);
        }

        return response()->json(['success' => false, 'message' => 'File not found.'], 404);
    }

    /**
     * Download a log file.
     */
    public function download(Request $request)
    {
        $file = basename($request->input('file', 'laravel.log'));
        $logPath = storage_path('logs/' . $file);

        if (File::exists($logPath) && str_ends_with($logPath, '.log')) {
            return response()->download($logPath, $file);
        }

        abort(404);
    }

    /**
     * Parse a Laravel log file into structured entries.
     */
    private function parseLogFile(string $path): array
    {
        $content = File::get($path);
        $entries = [];

        // Match Laravel log pattern: [YYYY-MM-DD HH:MM:SS] environment.LEVEL: Message
        $pattern = '/\[(\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2})\]\s(\w+)\.(\w+):\s(.*?)(?=\n\[\d{4}-\d{2}-\d{2}|\z)/s';

        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $i => $match) {
                $message = trim($match[4]);
                $context = '';
                $stacktrace = '';

                // Separate message from context/stacktrace
                if (str_contains($message, '[stacktrace]')) {
                    [$message, $stacktrace] = explode('[stacktrace]', $message, 2);
                    $message = trim($message);
                    $stacktrace = trim($stacktrace);
                }

                // Extract JSON context if present
                if (preg_match('/^(.*?)\s*(\{.*\})\s*$/s', $message, $contextMatch)) {
                    $message = trim($contextMatch[1]);
                    $context = trim($contextMatch[2]);
                }

                $entries[] = [
                    'id' => $i,
                    'timestamp' => $match[1],
                    'environment' => $match[2],
                    'level' => strtoupper($match[3]),
                    'message' => mb_substr($message, 0, 2000),
                    'context' => mb_substr($context, 0, 1000),
                    'stacktrace' => mb_substr($stacktrace, 0, 3000),
                    'has_stacktrace' => !empty($stacktrace),
                ];
            }
        }

        return $entries;
    }

    /**
     * Compute stats from entries.
     */
    private function computeStats(array $entries): array
    {
        $stats = [
            'total' => count($entries),
            'emergency' => 0, 'alert' => 0, 'critical' => 0,
            'error' => 0, 'warning' => 0, 'notice' => 0,
            'info' => 0, 'debug' => 0,
        ];

        foreach ($entries as $entry) {
            $level = strtolower($entry['level']);
            if (isset($stats[$level])) {
                $stats[$level]++;
            }
        }

        return $stats;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
