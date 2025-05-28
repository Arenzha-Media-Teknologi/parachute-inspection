<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\LastBackup;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\DbDumper\Databases\MySql;
use Illuminate\Support\Str;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class BackupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lastBackup = LastBackup::first();
        return view('web.backup.index', ['lastBackup' => $lastBackup]);
    }

    public function backupDatabase(Request $request)
    {
        // Configure environment
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');

        Log::info('========== STARTING BACKUP PROCESS ==========');
        Log::info('Start Time: ' . now()->toDateTimeString());

        // Initialize backup directory
        $backupDir = storage_path('app/public/backups');
        Log::info('Backup Directory: ' . $backupDir);

        try {
            // 1. Verify/Create backup directory with proper permissions
            $this->ensureBackupDirectoryExists($backupDir);

            $timestamp = now()->format('Ymd_His');
            $filename = 'backup_bundle_' . $timestamp . '.zip';
            $finalZipFile = $backupDir . '/' . $filename;

            // 2. Backup Database
            $sqlFile = $this->backupMysqlDatabase($backupDir, $timestamp);

            // 3. Backup Public Folder
            $publicZipFile = $this->backupPublicFolder($backupDir, $timestamp);

            // 4. Create Final Bundle
            $this->createFinalBackupBundle($finalZipFile, $sqlFile, $publicZipFile);

            // 5. Update LastBackup record
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

        // Verify directory is writable
        if (!is_writable($path)) {
            throw new Exception("Backup directory is not writable: {$path}");
        }
    }

    protected function backupMysqlDatabase(string $backupDir, string $timestamp): string
    {
        Log::info('--- STARTING DATABASE BACKUP ---');
        $sqlFile = $backupDir . '/database_' . $timestamp . '.sql';

        $command = sprintf(
            '"%s" --user=%s --password=%s --host=%s --port=%s --protocol=TCP %s > "%s"',
            env('DB_MYSQLDUMP_PATH', 'mysqldump'),
            env('DB_USERNAME', 'root'),
            env('DB_PASSWORD', ''),
            env('DB_HOST', '127.0.0.1'),
            env('DB_PORT', '3306'),
            config('database.connections.mysql.database'),
            $sqlFile
        );

        Log::info('Executing: ' . str_replace(env('DB_PASSWORD'), '*****', $command));

        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($sqlFile)) {
            throw new Exception("Database backup failed. Status: {$returnVar}. Output: " . implode("\n", $output));
        }

        Log::info('Database backup completed. Size: ' . filesize($sqlFile) . ' bytes');
        return $sqlFile;
    }

    protected function backupPublicFolder(string $backupDir, string $timestamp): string
    {
        Log::info('--- STARTING PUBLIC FOLDER BACKUP ---');
        $publicZipFile = $backupDir . '/public_' . $timestamp . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($publicZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create ZIP archive at: {$publicZipFile}");
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(public_path()),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $fileCount = 0;
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(public_path()) + 1);
                if (!$zip->addFile($filePath, $relativePath)) {
                    Log::warning("Failed to add file to ZIP: {$filePath}");
                }
                $fileCount++;
            }
        }

        if (!$zip->close()) {
            throw new Exception("Failed to close ZIP archive. Check permissions for: {$publicZipFile}");
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

        // Cleanup temporary files
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

        return response()->download(
            $filePath,
            basename($filePath),
            [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"'
            ]
        )->deleteFileAfterSend(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
