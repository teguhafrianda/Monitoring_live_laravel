<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SensorApiController extends Controller
{
    /**
     * Fetch data from Google Script and format it for the frontend.
     * This endpoint will be called by JavaScript.
     */
    public function fetchData()
    {
        // The URL of your Google Apps Script Web App
        $apiUrl = 'https://script.google.com/macros/s/AKfycbxZTYWzINpEnhDetvHrCVOG7bCtbH8hihk326DPhRlzbUJQpuNa_xVIY8tT4CMTr0nlBQ/exec';

        try {
            // Make a GET request to the API
            $response = Http::get($apiUrl);

            // If the request fails, return a JSON error response
            if (!$response->successful()) {
                Log::error('Failed to fetch data from Google Script: ' . $response->status());
                return response()->json(['error' => 'Gagal mengambil data dari sumber eksternal'], 502); // 502 Bad Gateway
            }

            $allData = $response->json();

            // If the data is empty, return a not found response
            if (empty($allData)) {
                return response()->json(['error' => 'Data tidak ditemukan'], 404);
            }

            // Get the last reading for the gauges
            $latestReading = end($allData);

            // --- Prepare data for the chart ---
            // Get the last 30 entries for the chart history, or fewer if not available
            $historicalData = array_slice($allData, -30);

            $chartData = [
                'labels' => [],
                'datasets' => [
                    'air_temperature' => [],
                    'air_humidity' => [],
                    'soil_temperature' => [],
                    'soil_humidity' => [],
                ]
            ];

            // Loop through historical data to populate chart arrays
            foreach ($historicalData as $data) {
                // Format timestamp to H:i:s for a clean chart label
                $chartData['labels'][] = Carbon::parse($data['timestamp'])->format('H:i:s');
                $chartData['datasets']['air_temperature'][] = $data['air_temperature'];
                $chartData['datasets']['air_humidity'][] = $data['air_humidity'];
                $chartData['datasets']['soil_temperature'][] = $data['temperature'];
                $chartData['datasets']['soil_humidity'][] = $data['humidity'];
            }

            // Return the final structured data as JSON
            return response()->json([
                'latestReading' => $latestReading,
                'chartData' => $chartData,
            ]);

        } catch (\Exception $e) {
            Log::error('API Controller Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Terjadi kesalahan pada server internal'], 500);
        }
    }
}
