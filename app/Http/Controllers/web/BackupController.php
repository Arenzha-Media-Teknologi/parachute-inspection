<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\LastBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Spatie\DbDumper\Databases\MySql;
use Illuminate\Support\Str;

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
        $backupPath = storage_path('app/backups/parachute_inspection.sql');

        $mysqldumpPath = env('DB_MYSQLDUMP_PATH', 'mysqldump'); // default jika tidak diset
        $dbHost = env('DB_HOST', '127.0.0.1');
        $dbPort = env('DB_PORT', '3306');
        $dbUser = env('DB_USERNAME', 'root');
        $dbPass = env('DB_PASSWORD', '');
        $dbName = config('database.connections.mysql.database');

        $command = sprintf(
            '"%s" --user=%s --password=%s --host=%s --port=%s --protocol=TCP %s > "%s"',
            $mysqldumpPath,
            $dbUser,
            $dbPass,
            $dbHost,
            $dbPort,
            $dbName,
            $backupPath
        );

        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            $fileSize = filesize($backupPath) / (1024 * 1024);

            LastBackup::updateOrCreate(
                ['id' => 1],
                [
                    'filename' => 'parachute_inspection.sql',
                    'path' => $backupPath,
                    'size_mb' => round($fileSize, 2),
                    'user_id' => Auth::id(),
                    'updated_at' => now()
                ]
            );

            return response()->download($backupPath);
        } else {
            return response()->json([
                'error' => 'Backup failed',
                'output' => $output,
                'returnVar' => $returnVar,
                'solution' => 'Pastikan mysqldump.exe ada di lokasi yang benar dan service MySQL sedang berjalan'
            ], 500);
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
