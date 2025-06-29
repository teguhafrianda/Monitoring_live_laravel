<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SensorApiController extends Controller
{
    /**
     * Mengambil data dari Google Script dan memformatnya untuk frontend.
     * Endpoint ini akan dipanggil oleh JavaScript secara berkala.
     */
    public function fetchData()
    {
        // URL Web App Google Apps Script Anda. Pastikan ini benar dan telah di-deploy dengan benar.
        $apiUrl = 'https://script.google.com/macros/s/AKfycbykH34WqD-G9iesYhJEF8OuA43Z-NhMJ8-CmsW2ZoLi-r1lr2Q1H8sc8qVs0ZhWaUs71g/exec';

        // Menggunakan blok try-catch adalah praktik terbaik untuk menangani potensi error
        // saat berkomunikasi dengan layanan eksternal.
        try {
            // Lakukan permintaan GET ke API Google Script.
            // timeout(15) akan membatalkan permintaan jika tidak ada respons dalam 15 detik.
            $response = Http::timeout(10)->get($apiUrl);

            // PERIKSA #1: Apakah koneksi ke Google Script berhasil?
            // Ini memeriksa status code HTTP (misal: 200 OK). Jika 404, 500, dll., maka gagal.
            if (!$response->successful()) {
                Log::error('Google Script mengembalikan status code gagal: ' . $response->status());
                return response()->json(['error' => 'Sumber data eksternal mengembalikan error.'], 502);
            }

            // Ambil seluruh body JSON dari respons.
            // Jika respons bukan JSON yang valid, ini akan melempar Exception dan ditangkap di blok catch.
            $responseData = $response->json();

            // PERIKSA #2: Apakah struktur JSON dari Google Script sesuai harapan?
            // Kita mengharapkan format: { "status": "success", "data": [...] }
            if (!isset($responseData['status']) || $responseData['status'] !== 'success' || !isset($responseData['data'])) {
                Log::error('Struktur JSON dari Google Script tidak valid.', $responseData ?? ['raw_body' => $response->body()]);
                return response()->json(['error' => 'Format data dari sumber eksternal tidak valid.'], 502);
            }
            
            // Ambil array data dari dalam properti 'data'
            $allData = $responseData['data'];

            // PERIKSA #3: Apakah properti 'data' benar-benar sebuah array?
            if (!is_array($allData)) {
                Log::error('Properti "data" dalam JSON bukan sebuah array.');
                return response()->json(['error' => 'Format data array tidak ditemukan.'], 500);
            }
            
            // Jika array data kosong (misal: sheet baru atau semua data terhapus)
            // Kirim respons kosong yang valid agar frontend tidak error.
            if (empty($allData)) {
                return response()->json(['latestReading' => null, 'chartData' => ['labels' => [], 'datasets' => []]]);
            }

            // PROSES #1: Bersihkan data.
            // Filter untuk menghapus baris yang mungkin kosong atau tidak memiliki timestamp di Google Sheet.
            $validData = array_filter($allData, function($row) {
                return !empty($row) && is_array($row) && isset($row['timestamp']) && !empty($row['timestamp']);
            });

            // Jika setelah difilter tidak ada data yang valid tersisa.
            if (empty($validData)) {
                Log::warning('Tidak ada baris data yang valid ditemukan setelah proses filter.');
                return response()->json(['error' => 'Tidak ada data valid yang ditemukan'], 404);
            }

            // PROSES #2: Siapkan data untuk frontend.
            
            // Ambil data paling akhir untuk ditampilkan di gauge (lingkaran).
            $latestReading = end($validData);

            // Ambil 30 data terakhir untuk ditampilkan di grafik.
            $historicalData = array_slice($validData, -30);

            // Inisialisasi struktur data untuk Chart.js
            $chartData = [
                'labels' => [],
                'datasets' => [
                    'air_temperature' => [],
                    'air_humidity' => [],
                    'soil_temperature' => [],
                    'soil_humidity' => [],
                ]
            ];

            // Loop melalui data historis untuk mengisi data grafik.
            foreach ($historicalData as $data) {
                // Carbon::parse digunakan untuk memformat tanggal.
                // '?? now()' digunakan sebagai fallback jika timestamp null.
                $chartData['labels'][] = Carbon::parse($data['timestamp'] ?? now())->format('H:i:s');
                
                // Mengisi data sensor.
                // '?? null' akan mengisi 'null' jika kunci array tidak ada, ini mencegah error.
                $chartData['datasets']['air_temperature'][] = $data['air_temperature'] ?? null;
                $chartData['datasets']['air_humidity'][] = $data['air_humidity'] ?? null;
                $chartData['datasets']['soil_temperature'][] = $data['temperature'] ?? null;
                $chartData['datasets']['soil_humidity'][] = $data['humidity'] ?? null;
            }

            // Kirim data akhir yang sudah bersih dan terstruktur ke frontend.
            return response()->json([
                'latestReading' => $latestReading,
                'chartData' => $chartData,
            ]);

        // Tangani jenis-jenis error secara spesifik.
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // Error ini terjadi jika Laravel tidak bisa terhubung sama sekali ke URL Google Script.
            Log::error('Gagal terhubung ke Google Script: ' . $e->getMessage());
            return response()->json(['error' => 'Tidak dapat terhubung ke server data.'], 504); // 504 Gateway Timeout
        
        } catch (\Exception $e) {
            // 'catch-all' untuk error lain, seperti jika $response->json() gagal karena respons bukan JSON.
            Log::error('API Controller Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan pada server saat memproses data.'], 500);
        }
    }
}
