<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    private $backupDir = '/var/backups/mysql';

    public function index()
    {
        $backups = $this->getBackups();

        return view('backups.index', compact('backups'));
    }

    private function getBackups()
    {
        $backups = [];

        // Vérifier si le répertoire existe
        if (!is_dir($this->backupDir)) {
            return $backups;
        }

        // Lister tous les fichiers de sauvegarde
        $files = glob($this->backupDir . '/kabas_*.sql.gz');

        foreach ($files as $file) {
            $filename = basename($file);

            // Extraire la date du nom de fichier (format: kabas_YYYYMMDD_HHMMSS.sql.gz)
            if (preg_match('/kabas_(\d{8})_(\d{6})\.sql\.gz/', $filename, $matches)) {
                $dateStr = $matches[1];
                $timeStr = $matches[2];

                $year = substr($dateStr, 0, 4);
                $month = substr($dateStr, 4, 2);
                $day = substr($dateStr, 6, 2);

                $hour = substr($timeStr, 0, 2);
                $minute = substr($timeStr, 2, 2);
                $second = substr($timeStr, 4, 2);

                $datetime = "$year-$month-$day $hour:$minute:$second";

                $backups[] = [
                    'filename' => $filename,
                    'path' => $file,
                    'date' => $datetime,
                    'timestamp' => strtotime($datetime),
                    'size' => filesize($file),
                    'size_human' => $this->formatBytes(filesize($file)),
                ];
            }
        }

        // Trier par date décroissante (plus récent en premier)
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });

        return $backups;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function download($filename)
    {
        $file = $this->backupDir . '/' . $filename;

        // Vérifier que le fichier existe et est bien un fichier de sauvegarde
        if (!file_exists($file) || !preg_match('/^kabas_\d{8}_\d{6}\.sql\.gz$/', $filename)) {
            abort(404);
        }

        return response()->download($file);
    }

    public function create(Request $request)
    {
        try {
            // Exécuter le script de sauvegarde
            $output = [];
            $returnCode = 0;

            exec('sudo /usr/local/bin/mysql-backup.sh 2>&1', $output, $returnCode);

            if ($returnCode === 0) {
                return redirect()->route('backups.index')
                    ->with('success', __('messages.backup.created_success'));
            } else {
                return redirect()->route('backups.index')
                    ->with('error', __('messages.backup.created_error') . ': ' . implode("\n", $output));
            }
        } catch (\Exception $e) {
            return redirect()->route('backups.index')
                ->with('error', __('messages.backup.created_error') . ': ' . $e->getMessage());
        }
    }

    public function delete($filename)
    {
        $file = $this->backupDir . '/' . $filename;

        // Vérifier que le fichier existe et est bien un fichier de sauvegarde
        if (!file_exists($file) || !preg_match('/^kabas_\d{8}_\d{6}\.sql\.gz$/', $filename)) {
            abort(404);
        }

        try {
            unlink($file);
            return redirect()->route('backups.index')
                ->with('success', __('messages.backup.deleted_success'));
        } catch (\Exception $e) {
            return redirect()->route('backups.index')
                ->with('error', __('messages.backup.deleted_error') . ': ' . $e->getMessage());
        }
    }
}
