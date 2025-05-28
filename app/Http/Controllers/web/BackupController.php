<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\LastBackup;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

        Log::info('========== STARTING GOOGLE DRIVE BACKUP PROCESS ==========');
        Log::info('Start Time: ' . now()->toDateTimeString());

        try {
            $timestamp = now()->format('Ymd_His');
            $backupName = 'backup_' . $timestamp;

            // 1. Backup Database
            $sqlContent = $this->backupMysqlDatabase();

            // 2. Backup Public Folder
            $publicZipPath = $this->backupPublicFolder();

            // 3. Upload to Google Drive
            $drivePath = $this->uploadToGoogleDrive($backupName, $sqlContent, $publicZipPath);

            // 4. Update LastBackup record
            $this->updateBackupRecord($backupName, $drivePath);

            Log::info('========== BACKUP PROCESS COMPLETED ==========');
            return response()->json([
                'success' => true,
                'drive_url' => $drivePath,
                'message' => 'Backup successfully uploaded to Google Drive'
            ]);
        } catch (Exception $e) {
            Log::error('========== BACKUP PROCESS FAILED ==========');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            // Clean up temporary files if they exist
            if (isset($publicZipPath) && file_exists($publicZipPath)) {
                @unlink($publicZipPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    protected function backupMysqlDatabase(): string
    {
        Log::info('--- STARTING DATABASE BACKUP ---');

        $command = sprintf(
            '"%s" --user=%s --password=%s --host=%s --port=%s --protocol=TCP %s',
            env('DB_MYSQLDUMP_PATH', 'mysqldump'),
            env('DB_USERNAME', 'root'),
            env('DB_PASSWORD', ''),
            env('DB_HOST', '127.0.0.1'),
            env('DB_PORT', '3306'),
            config('database.connections.mysql.database')
        );

        Log::info('Executing: ' . str_replace(env('DB_PASSWORD'), '*****', $command));

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("Database backup failed. Status: {$returnVar}. Output: " . implode("\n", $output));
        }

        $sqlContent = implode("\n", $output);
        Log::info('Database backup completed. Size: ' . strlen($sqlContent) . ' bytes');

        return $sqlContent;
    }

    protected function backupPublicFolder(): string
    {
        Log::info('--- STARTING PUBLIC FOLDER BACKUP ---');
        $zipPath = storage_path('app/temp_public_backup_' . now()->format('Ymd_His') . '.zip');

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Failed to create ZIP archive at: {$zipPath}");
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
            throw new Exception("Failed to close ZIP archive: {$zipPath}");
        }

        Log::info("Public folder backup completed. Files: {$fileCount}, Size: " . filesize($zipPath) . ' bytes');
        return $zipPath;
    }

    protected function uploadToGoogleDrive(string $folderName, string $sqlContent, string $publicZipPath): string
    {
        Log::info('--- UPLOADING TO GOOGLE DRIVE ---');

        // Create folder in Google Drive
        Storage::disk('google')->makeDirectory($folderName);

        // Upload database backup
        Storage::disk('google')->put(
            "{$folderName}/database.sql",
            $sqlContent
        );

        // Upload public folder backup
        Storage::disk('google')->put(
            "{$folderName}/public.zip",
            fopen($publicZipPath, 'r+')
        );

        // Clean up temporary file
        @unlink($publicZipPath);

        Log::info('Backup successfully uploaded to Google Drive');
        return $folderName;
    }

    protected function updateBackupRecord(string $folderName, string $drivePath): void
    {
        // Get file size from Google Drive
        $size = 0;
        try {
            $size = Storage::disk('google')->size("{$drivePath}/public.zip") / 1024 / 1024;
        } catch (Exception $e) {
            Log::error("Failed to get file size from Google Drive: " . $e->getMessage());
        }

        LastBackup::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'filename' => $folderName,
                'path' => $drivePath,
                'size_mb' => round($size, 2),
                'updated_at' => now(),
            ]
        );
    }

    public function downloadBackup($folderName)
    {
        try {
            // Check if folder exists
            $files = Storage::disk('google')->files($folderName);

            if (empty($files)) {
                abort(404, 'Backup not found on Google Drive');
            }

            // Get the public.zip file
            $filePath = "{$folderName}/public.zip";

            if (!Storage::disk('google')->exists($filePath)) {
                abort(404, 'Backup file not found');
            }

            $fileContent = Storage::disk('google')->get($filePath);

            return response($fileContent)
                ->header('Content-Type', 'application/zip')
                ->header('Content-Disposition', 'attachment; filename="' . $folderName . '.zip"');
        } catch (Exception $e) {
            Log::error('Download failed: ' . $e->getMessage());
            abort(500, 'Failed to download backup');
        }
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
