<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\LastBackup;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class BackupController extends Controller
{
    // ... (Fungsi index() dan fungsi lain yang tidak berubah tetap sama) ...

    public function index()
    {
        $lastBackup = LastBackup::first();
        return view('web.backup.index', ['lastBackup' => $lastBackup]);
    }

    public function backupDatabase(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        Log::info('========== STARTING BACKUP PROCESS ==========');
        Log::info('Start Time: ' . now()->toDateTimeString());

        $backupDir = storage_path('app/public/backups');
        Log::info('Backup Directory: ' . $backupDir);

        try {
            $this->ensureBackupDirectoryExists($backupDir);
            $timestamp = now()->format('Ymd_His');
            $filename = 'backup_bundle_' . $timestamp . '.zip';
            $finalZipFile = $backupDir . '/' . $filename;
            $sqlFile = $this->backupMysqlDatabase($backupDir, $timestamp);
            $publicZipFile = $this->backupPublicFolder($backupDir, $timestamp);
            $this->createFinalBackupBundle($finalZipFile, $sqlFile, $publicZipFile);
            $this->updateBackupRecord($filename, $finalZipFile);

            Log::info('========== BACKUP PROCESS COMPLETED ==========');
            return response()->json([
                'success' => true,
                'filename' => $filename,
                'download_url' => route('backup.download', ['filename' => $filename]),
                'file_size' => filesize($finalZipFile),
                'message' => 'Backup created successfully'
            ]);
        } catch (Exception $e) {
            Log::error('========== BACKUP PROCESS FAILED ==========');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    protected function ensureBackupDirectoryExists(string $path): void
    {
        if (!file_exists($path)) {
            Log::info('Creating backup directory...');
            if (!mkdir($path, 0755, true)) {
                throw new Exception("Failed to create backup directory at: {$path}");
            }
            Log::info('Backup directory created');
        }
        if (!is_writable($path)) {
            throw new Exception("Backup directory is not writable: {$path}");
        }
    }

    protected function backupMysqlDatabase(string $backupDir, string $timestamp): string
    {
        Log::info('--- STARTING DATABASE BACKUP ---');
        $sqlFile = $backupDir . '/database_' . $timestamp . '.sql';
        $dumpPath = env('DB_MYSQLDUMP_PATH', 'mysqldump');

        // Menambahkan kutip ganda di sekitar path mysqldump untuk menangani spasi
        $command = sprintf(
            '"%s" --user=%s --password=%s --host=%s --port=%s --protocol=TCP %s > "%s"',
            $dumpPath,
            env('DB_USERNAME', 'root'),
            env('DB_PASSWORD', ''),
            env('DB_HOST', '127.0.0.1'),
            env('DB_PORT', '3306'),
            config('database.connections.mysql.database'),
            $sqlFile
        );

        Log::info('Executing: ' . str_replace(env('DB_PASSWORD'), '*****', $command));
        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($sqlFile) || filesize($sqlFile) === 0) {
            throw new Exception("Database backup failed. Status: {$returnVar}. Output: " . implode("\n", $output));
        }

        Log::info('Database backup completed. Size: ' . filesize($sqlFile) . ' bytes');
        return $sqlFile;
    }

    /**
     * =================================================================
     * FUNGSI YANG DIPERBAIKI DENGAN LOGIKA LEBIH ROBUST
     * =================================================================
     */
    protected function backupPublicFolder(string $backupDir, string $timestamp): string
    {
        Log::info('--- STARTING PUBLIC FOLDER BACKUP ---');
        $publicZipFile = $backupDir . '/public_' . $timestamp . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($publicZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create ZIP archive at: {$publicZipFile}");
        }

        $sourcePath = public_path();
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        // Dapatkan path absolut dari symlink storage untuk perbandingan yang andal
        $storageSymlinkPath = realpath(public_path('storage'));

        $fileCount = 0;
        foreach ($files as $file) {
            $filePath = $file->getRealPath();

            // Lewati jika file tidak bisa dibaca atau merupakan link yang rusak
            if ($filePath === false) {
                Log::warning('Skipping unreadable file: ' . $file->getPathname());
                continue;
            }

            // == PERBAIKAN UTAMA: START ==
            // Cek jika path file berada di dalam path symlink storage yang sebenarnya
            if ($storageSymlinkPath !== false && strpos($filePath, $storageSymlinkPath) === 0) {
                continue;
            }
            // == PERBAIKAN UTAMA: END ==

            $relativePath = substr($filePath, strlen($sourcePath) + 1);
            if (!$zip->addFile($filePath, $relativePath)) {
                Log::warning("Failed to add file to ZIP: {$filePath}");
            }
            $fileCount++;
        }

        if (!$zip->close()) {
            throw new Exception("Failed to close ZIP archive. Status: " . $zip->statusString());
        }

        Log::info("Public folder backup completed. Files: {$fileCount}, Size: " . filesize($publicZipFile) . ' bytes');
        return $publicZipFile;
    }

    protected function createFinalBackupBundle(string $finalZipFile, string $sqlFile, string $publicZipFile): void
    {
        Log::info('--- CREATING FINAL BACKUP BUNDLE ---');
        $zip = new ZipArchive();
        if ($zip->open($finalZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create final backup bundle at: {$finalZipFile}");
        }
        $zip->addFile($sqlFile, 'database.sql');
        $zip->addFile($publicZipFile, 'public.zip');
        if (!$zip->close()) {
            throw new Exception("Failed to finalize backup bundle. Check permissions for: {$finalZipFile}");
        }
        @unlink($sqlFile);
        @unlink($publicZipFile);
        if (!file_exists($finalZipFile)) {
            throw new Exception("Final backup file not created at: {$finalZipFile}");
        }
        Log::info('Final backup created. Size: ' . filesize($finalZipFile) . ' bytes');
    }

    protected function updateBackupRecord(string $filename, string $finalZipFile): void
    {
        LastBackup::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'filename' => $filename,
                'path' => $finalZipFile,
                'size_mb' => round(filesize($finalZipFile) / 1024 / 1024, 2),
                'updated_at' => now(),
            ]
        );
    }

    public function downloadBackup($filename)
    {
        $backupDir = storage_path('app/public/backups');
        $filePath = $backupDir . '/' . $filename;
        if (!file_exists($filePath)) {
            abort(404, 'Backup file not found');
        }
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'zip') {
            abort(400, 'Invalid file format');
        }
        return response()->download($filePath, basename($filePath))->deleteFileAfterSend(true);
    }

    // ... (Fungsi create, store, show, edit, update, destroy tetap sama) ...
}
