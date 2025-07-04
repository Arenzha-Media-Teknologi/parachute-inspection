<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\LastBackup;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yaza\LaravelGoogleDriveStorage\Gdrive;
use Illuminate\Support\Str;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class BackupControllerGDRIVE extends Controller
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

            // 3. Upload to Google Drive (langsung ke folder yang ditentukan)
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



    protected function uploadToGoogleDrive(string $backupName, string $sqlContent, string $publicZipPath): string
    {
        try {
            // Upload file menggunakan Service Account
            Gdrive::put("{$backupName}_database.sql", $sqlContent);
            Gdrive::put("{$backupName}_public.zip", file_get_contents($publicZipPath));

            // Hapus file temporary
            @unlink($publicZipPath);

            return $backupName;
        } catch (Exception $e) {
            @unlink($publicZipPath);
            throw new Exception("Google Drive upload failed: " . $e->getMessage());
        }
    }

    protected function updateBackupRecord(string $backupName, string $drivePath): void
    {
        // Get file size from Google Drive
        $size = 0;
        try {
            $publicFileName = "{$backupName}_public.zip";
            $size = Storage::disk('google')->size($publicFileName) / 1024 / 1024;
        } catch (Exception $e) {
            Log::error("Failed to get file size from Google Drive: " . $e->getMessage());
        }

        LastBackup::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'filename' => $backupName,
                'path' => $drivePath,
                'size_mb' => round($size, 2),
                'updated_at' => now(),
            ]
        );
    }

    public function downloadBackup($backupName)
    {
        try {
            $publicFileName = "{$backupName}_public.zip";

            if (!Storage::disk('google')->exists($publicFileName)) {
                abort(404, 'Backup file not found');
            }

            $fileContent = Storage::disk('google')->get($publicFileName);

            return response($fileContent)
                ->header('Content-Type', 'application/zip')
                ->header('Content-Disposition', 'attachment; filename="' . $backupName . '.zip"');
        } catch (Exception $e) {
            Log::error('Download failed: ' . $e->getMessage());
            abort(500, 'Failed to download backup');
        }
    }
}
