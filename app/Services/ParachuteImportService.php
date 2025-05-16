<?php

namespace App\Services;

use App\Models\Parachute;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Exception;
use Illuminate\Support\Facades\Log;

class ParachuteImportService
{
    public function import($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("File tidak ditemukan: " . $filePath);
        }

        try {
            $fileExt = $this->detectActualFileType($filePath);

            switch ($fileExt) {
                case 'xlsx':
                    return $this->handleXlsxImport($filePath);
                case 'xls':
                    return $this->importXls($filePath);
                case 'csv':
                    return $this->importCsv($filePath);
                default:
                    throw new Exception("Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv");
            }
        } catch (Exception $e) {
            Log::error('Import Error: ' . $e->getMessage(), [
                'file' => $filePath,
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception("Gagal mengimpor file: " . $e->getMessage());
        }
    }

    protected function detectActualFileType($filePath)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // Baca signature file
        $file = fopen($filePath, 'rb');
        $firstBytes = fread($file, 8);
        fclose($file);

        // Deteksi berdasarkan magic number
        if (str_starts_with($firstBytes, "\x50\x4B\x03\x04")) {
            return 'xlsx';
        } elseif (str_starts_with($firstBytes, "\xD0\xCF\x11\xE0")) {
            return 'xls';
        } elseif ($extension === 'csv') {
            return 'csv';
        }

        return $extension; // Fallback ke ekstensi file
    }

    protected function handleXlsxImport($filePath)
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception(
                "Ekstensi ZIP tidak tersedia. " .
                    "Silakan konversi file ke format .xls atau .csv terlebih dahulu."
            );
        }

        return $this->importXlsx($filePath);
    }

    protected function importXlsx($filePath)
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        return $this->processSpreadsheet($reader->load($filePath));
    }

    protected function importXls($filePath)
    {
        $reader = IOFactory::createReader('Xls');
        $reader->setReadDataOnly(true);
        return $this->processSpreadsheet($reader->load($filePath));
    }

    protected function importCsv($filePath)
    {
        $reader = IOFactory::createReader('Csv');
        $reader->setInputEncoding('CP1252');
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);

        return $this->processSpreadsheet($reader->load($filePath));
    }

    protected function processSpreadsheet($spreadsheet)
    {
        $worksheet = $spreadsheet->getActiveSheet();
        $importedData = [];
        $startRow = 3; // Header row diabaikan

        $highestRow = $worksheet->getHighestDataRow();
        $highestColumn = $worksheet->getHighestDataColumn();

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $serialNumber = $worksheet->getCell('B' . $row)->getValue();

            // Skip baris kosong
            if (empty($serialNumber)) {
                continue;
            }

            try {
                $parachute = Parachute::create([
                    'serial_number' => $serialNumber,
                    'part_number' => $worksheet->getCell('C' . $row)->getValue(),
                    'type' => $worksheet->getCell('D' . $row)->getValue(),
                    'category' => $worksheet->getCell('E' . $row)->getValue(),
                    'created_by' => 1, // Ganti dengan auth()->id() jika menggunakan auth
                ]);

                $importedData[] = $parachute;
            } catch (Exception $e) {
                Log::warning('Gagal mengimpor baris ' . $row . ': ' . $e->getMessage());
                continue;
            }
        }

        return $importedData;
    }
}
