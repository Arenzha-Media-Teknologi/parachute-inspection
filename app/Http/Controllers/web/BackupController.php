<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\LastBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\DbDumper\Databases\MySql;
use Illuminate\Support\Str;
use ZipArchive;

class BackupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lastBackup = LastBackup::first();

        return view('web.backup.index', [
            'lastBackup' => $lastBackup,
        ]);
    }




    public function backupDatabase(Request $request)
    {
        // Set execution time and memory
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M');

        // Initialize logging
        Log::info('========== STARTING BACKUP PROCESS ==========');
        Log::info('Start Time: ' . now()->toDateTimeString());

        $backupDir = storage_path('app/public/backups');
        Log::info('Backup Directory: ' . $backupDir);

        if (!file_exists($backupDir)) {
            Log::info('Creating backup directory...');
            if (!mkdir($backupDir, 0755, true)) {
                $error = "Failed to create backup directory";
                Log::error($error);
                return response()->json([
                    'success' => false,
                    'message' => $error
                ], 500);
            }
            Log::info('Backup directory created successfully');
        }

        $timestamp = now()->format('Ymd_His');
        $filename = 'backup_bundle_' . $timestamp . '.zip';
        $finalZipFile = $backupDir . '/' . $filename;
        Log::info('Final backup file: ' . $finalZipFile);

        try {
            // 1. Database Backup
            Log::info('--- STARTING DATABASE BACKUP ---');
            $sqlFile = $backupDir . '/database_' . $timestamp . '.sql';
            Log::info('SQL file: ' . $sqlFile);

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

            Log::info('Executing command: ' . str_replace(env('DB_PASSWORD'), '*****', $command));

            exec($command, $output, $returnVar);
            Log::info('Command output: ' . json_encode($output));
            Log::info('Return status: ' . $returnVar);

            if ($returnVar !== 0) {
                $error = "Database backup failed: " . implode("\n", $output);
                Log::error($error);
                throw new \Exception($error);
            }

            Log::info('Database backup successful. File size: ' . filesize($sqlFile) . ' bytes');

            // 2. Public Folder Backup
            Log::info('--- STARTING PUBLIC FOLDER BACKUP ---');
            $publicZipFile = $backupDir . '/public_' . $timestamp . '.zip';
            Log::info('Public ZIP file: ' . $publicZipFile);

            $zip = new ZipArchive();
            if ($zip->open($publicZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $error = "Failed to create public folder ZIP";
                Log::error($error);
                throw new \Exception($error);
            }

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(public_path()),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            $fileCount = 0;
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen(public_path()) + 1);
                    $zip->addFile($filePath, $relativePath);
                    $fileCount++;
                }
            }
            $zip->close();
            Log::info("Public folder backup successful. Files: {$fileCount}, Size: " . filesize($publicZipFile) . ' bytes');

            // 3. Create Final Backup Bundle
            Log::info('--- CREATING FINAL BACKUP BUNDLE ---');
            $finalZip = new ZipArchive();
            if ($finalZip->open($finalZipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                $error = "Failed to create final ZIP bundle";
                Log::error($error);
                throw new \Exception($error);
            }

            $finalZip->addFile($sqlFile, 'database.sql');
            $finalZip->addFile($publicZipFile, 'public.zip');
            $finalZip->close();

            // Verify final backup file
            if (!file_exists($finalZipFile)) {
                $error = "Backup file not created";
                Log::error($error);
                throw new \Exception($error);
            }

            $finalSize = filesize($finalZipFile);
            if ($finalSize === 0) {
                $error = "Backup file is empty";
                Log::error($error);
                throw new \Exception($error);
            }

            Log::info("Final backup successful. Size: {$finalSize} bytes");

            // Clean up temporary files
            Log::info('Cleaning up temporary files...');
            @unlink($sqlFile);
            @unlink($publicZipFile);

            // Completion log
            Log::info('========== BACKUP PROCESS COMPLETED ==========');
            Log::info('Completion Time: ' . now()->toDateTimeString());
            Log::info('Final file: ' . $finalZipFile);
            Log::info('Final size: ' . $finalSize . ' bytes');

            LastBackup::updateOrCreate(
                ['user_id' => Auth::id()],
                [
                    'filename' => $filename,
                    'path' => $finalZipFile,
                    'size_mb' => round($finalSize / 1024 / 1024, 2),
                    'updated_at' => now(),
                ]
            );

            // Return JSON response with download URL
            return response()->json([
                'success' => true,
                'filename' => $filename,
                'download_url' => route('backup.download', ['filename' => $filename]),
                'file_size' => $finalSize,
                'message' => 'Backup created successfully'
            ]);
        } catch (\Exception $e) {
            // Error logging
            Log::error('========== BACKUP PROCESS FAILED ==========');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            Log::error('Error Time: ' . now()->toDateTimeString());

            // Clean up potentially corrupted files
            $filesToDelete = [
                $finalZipFile ?? null,
                $sqlFile ?? null,
                $publicZipFile ?? null
            ];

            foreach ($filesToDelete as $file) {
                if ($file && file_exists($file)) {
                    Log::info('Deleting file: ' . $file);
                    @unlink($file);
                }
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }



    public function downloadBackup($filename)
    {
        $backupDir = storage_path('app/public/backups');
        $filePath = $backupDir . '/' . $filename;

        // Validasi file
        if (!file_exists($filePath)) {
            abort(404, 'File backup tidak ditemukan');
        }

        // Validasi ekstensi file untuk keamanan
        if (pathinfo($filename, PATHINFO_EXTENSION) !== 'zip') {
            abort(400, 'Format file tidak valid');
        }

        // Headers untuk download
        $headers = [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
            'Content-Length' => filesize($filePath),
        ];

        // Log sebelum download
        Log::info('Memulai download backup file: ' . $filePath);
        Log::info('Ukuran file: ' . filesize($filePath) . ' bytes');

        // Return response download dengan callback untuk menghapus file setelah selesai
        return response()->download($filePath, basename($filePath), $headers)->deleteFileAfterSend(true);
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
