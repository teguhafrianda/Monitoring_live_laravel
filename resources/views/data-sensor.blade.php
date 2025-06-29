<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Dashboard Sensor Dinamis</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gauge-bg-color: #e2e8f0;
            --gauge-inner-color: #ffffff;
            --text-color-primary: #1a202c;
            --text-color-secondary: #718096;
        }

        body { font-family: 'Roboto', sans-serif; background-color: #f0f2f5; color: #333; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 2rem; box-sizing: border-box; }
        .container { width: 100%; max-width: 1400px; }
        .header { text-align: center; margin-bottom: 2rem; }
        .header h1 { font-weight: 700; color: var(--text-color-primary); }
        .header p { color: #666; font-size: 1.1rem; }
        .header .timestamp { font-size: .9rem; color: #555; background-color: #e2e8f0; display: inline-block; padding: 8px 15px; border-radius: 20px; margin-top: 10px; min-height: 24px; font-weight: 500; transition: background-color 0.3s ease; }
        .header .timestamp.updated { background-color: #dcfce7; color: #166534; }
        .gauge-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1.5rem; justify-content: center; margin-bottom: 2rem; }

        /* --- CSS Untuk Gauge Dinamis --- */
        .gauge-circle {
            display: grid;
            place-items: center; /* Menengahkan elemen anak */
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 50%;
            /* 'conic-gradient' adalah kuncinya. Diatur oleh variabel --p dari JS */
            background: conic-gradient(var(--color, #3b82f6) calc(var(--p, 0) * 1%), var(--gauge-bg-color) 0);
            transition: background 0.5s ease-in-out;
        }
        .gauge-inner {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 85%; /* Membuat efek 'cincin' */
            height: 85%;
            background: var(--gauge-inner-color);
            border-radius: 50%;
            box-shadow: inset 0 0 15px rgba(0,0,0,0.05);
        }
        .gauge-value { font-size: clamp(1.4rem, 4vw, 2.1rem); font-weight: 700; color: var(--text-color-primary); line-height: 1; }
        .gauge-value small { font-size: 0.8rem; font-weight: 500; margin-left: 2px; }
        .gauge-label { font-size: clamp(0.8rem, 2vw, 0.9rem); font-weight: 400; color: var(--text-color-secondary); margin-top: 8px; text-align: center; padding: 0 5px; }

        /* --- Warna Spesifik untuk Setiap Gauge --- */
        :root {
            --color-temp: #ef4444;
            --color-humidity: #3b82f6;
            --color-ph: #8b5cf6;
            --color-nitro: #16a34a;
            --color-other: #64748b;
        }

        .chart-wrapper { background-color: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.07); }
        .loader-container { text-align: center; padding: 5rem 0; }
        .loader { border: 8px solid #f3f3f3; border-radius: 50%; border-top: 8px solid #3b82f6; width: 60px; height: 60px; animation: spin 1.5s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .alert-error { text-align: center; padding: 1.5rem; border-radius: 8px; background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Live Dashboard Sensor Dinamis</h1>
            <p>Data diperbarui secara otomatis setiap 10 detik.</p>
            <div class="timestamp" id="timestamp-display">Menunggu data...</div>
        </div>
        <div id="dashboard-content">
            <div class="loader-container" id="loader">
                <div class="loader"></div>
                <p style="margin-top: 1rem; color: #666;">Memuat data awal...</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>

    <script>
        let sensorChart;

        // ... (Fungsi createDashboardElements, initializeChart, SENSOR_RANGES, dll tetap sama) ...
        function createDashboardElements() {
            const content = `<div class="gauge-grid" id="gauge-container"></div> <div class="chart-wrapper"><canvas id="sensorChart"></canvas></div>`;
            $('#dashboard-content').html(content);
        }

        function initializeChart() {
            const ctx = document.getElementById('sensorChart').getContext('2d');
            sensorChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        { label: 'Suhu Udara (°C)', data: [], borderColor: 'var(--color-temp)', backgroundColor: 'rgba(239, 68, 68, 0.1)', borderWidth: 2, fill: true, tension: 0.4 },
                        { label: 'Kelembaban Udara (%)', data: [], borderColor: 'var(--color-humidity)', backgroundColor: 'rgba(59, 130, 246, 0.1)', borderWidth: 2, fill: true, tension: 0.4 },
                        { label: 'Suhu Tanah (°C)', data: [], borderColor: '#f97316', backgroundColor: 'rgba(249, 115, 22, 0.1)', borderWidth: 2, fill: true, tension: 0.4, hidden: true },
                        { label: 'Kelembaban Tanah (%)', data: [], borderColor: 'var(--color-nitro)', backgroundColor: 'rgba(22, 163, 74, 0.1)', borderWidth: 2, fill: true, tension: 0.4, hidden: true }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        // Sumbu Y sekarang tidak memiliki konfigurasi min/max/beginAtZero di awal.
                        // Akan diatur secara dinamis oleh JavaScript.
                        y: {}
                    },
                    plugins: {
                        legend: { position: 'top', onClick: (e, legendItem, legend) => {
                            // Fungsi default untuk menyembunyikan/menampilkan dataset
                            Chart.defaults.plugins.legend.onClick(e, legendItem, legend);
                            // Panggil fungsi untuk mengkalkulasi ulang sumbu Y setelah user mengklik legenda
                            updateYAxis(legend.chart);
                        }},
                        title: { display: true, text: 'Grafik Tren Sensor (30 Data Terakhir)', font: { size: 16 } }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
        }
        
        const SENSOR_RANGES = {
            default: { min: 0, max: 100 }, air_temperature: { min: 0, max: 100 }, temperature: { min: 0, max: 100 },
            humidity: { min: 0, max: 100 }, air_humidity: { min: 0, max: 100 }, ph: { min: 0, max: 14 },
            nitrogen: { min: 0, max: 250 }, phosphorus: { min: 0, max: 250 }, potassium: { min: 0, max: 250 }
        };

        function calculatePercentage(value, type) { const range = SENSOR_RANGES[type] || SENSOR_RANGES.default; if (value === null || isNaN(parseFloat(value))) return 0; let val = parseFloat(value); val = Math.max(range.min, Math.min(range.max, val)); const percentage = ((val - range.min) / (range.max - range.min)) * 100; return percentage.toFixed(2); }
        function createGaugeHTML(type, value, unit, label) { const percentage = calculatePercentage(value, type); const displayValue = (value !== null && !isNaN(parseFloat(value))) ? parseFloat(value).toFixed(1) : 'N/A'; const displayUnit = (displayValue !== 'N/A') ? `<small>${unit}</small>` : ''; let colorVar = '--color-other'; if (type.includes('temp')) colorVar = '--color-temp'; else if (type.includes('humi')) colorVar = '--color-humidity'; else if (type === 'ph') colorVar = '--color-ph'; else if (type === 'nitrogen') colorVar = '--color-nitro'; return `<div><div class="gauge-circle" style="--p:${percentage}; --color:var(${colorVar})"><div class="gauge-inner"><div class="gauge-value">${displayValue}${displayUnit}</div></div></div><div class="gauge-label">${label}</div></div>`; }

        // --- FUNGSI BARU: Untuk memperbarui Sumbu Y secara dinamis ---
        function updateYAxis(chart) {
            let allVisibleData = [];
            
            // Kumpulkan semua data dari dataset yang sedang terlihat
            chart.data.datasets.forEach((dataset, index) => {
                if (chart.isDatasetVisible(index)) {
                    allVisibleData.push(...dataset.data);
                }
            });

            // Filter nilai null atau undefined
            allVisibleData = allVisibleData.filter(v => v !== null && v !== undefined);

            // Jika tidak ada data, jangan lakukan apa-apa
            if (allVisibleData.length === 0) {
                return;
            }

            const dataMin = Math.min(...allVisibleData);
            const dataMax = Math.max(...allVisibleData);

            // Jika semua nilai data sama
            if (dataMin === dataMax) {
                chart.options.scales.y.min = dataMin - 5; // Beri ruang 5 unit
                chart.options.scales.y.max = dataMax + 5;
            } else {
                // Tambahkan padding 10% di atas dan di bawah
                const padding = (dataMax - dataMin) * 0.1;
                chart.options.scales.y.min = Math.floor(dataMin - padding);
                chart.options.scales.y.max = Math.ceil(dataMax + padding);
            }
        }

        function fetchDataAndUpdate() {
            $.ajax({
                url: '/api/sensor-data',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if ($('#loader').length) {
                        createDashboardElements();
                        initializeChart();
                    }
                    
                    // 1. UPDATE GAUGE
                    const gauges = response.latestReading;
                    const gaugeContainer = $('#gauge-container');
                    gaugeContainer.html('');
                    gaugeContainer.append(createGaugeHTML('air_temperature', gauges.air_temperature, '°C', 'Suhu Udara'));
                    gaugeContainer.append(createGaugeHTML('air_humidity', gauges.air_humidity, '%', 'Kelembaban Udara'));
                    gaugeContainer.append(createGaugeHTML('temperature', gauges.temperature, '°C', 'Suhu Tanah'));
                    gaugeContainer.append(createGaugeHTML('humidity', gauges.humidity, '%', 'Kelembaban Tanah'));
                    gaugeContainer.append(createGaugeHTML('ph', gauges.ph, '', 'pH Tanah'));
                    gaugeContainer.append(createGaugeHTML('nitrogen', gauges.nitrogen, ' mg/kg', 'Nitrogen (N)'));
                    gaugeContainer.append(createGaugeHTML('phosphorus', gauges.phosphorus, ' mg/kg', 'Fosfor (P)'));
                    gaugeContainer.append(createGaugeHTML('potassium', gauges.potassium, ' mg/kg', 'Kalium (K)'));
                    
                    // 2. UPDATE DATA GRAFIK
                    const chartData = response.chartData;
                    sensorChart.data.labels = chartData.labels;
                    sensorChart.data.datasets[0].data = chartData.datasets.air_temperature;
                    sensorChart.data.datasets[1].data = chartData.datasets.air_humidity;
                    sensorChart.data.datasets[2].data = chartData.datasets.soil_temperature;
                    sensorChart.data.datasets[3].data = chartData.datasets.soil_humidity;
                    
                    // 3. Panggil fungsi untuk mengatur ulang skala Sumbu Y
                    updateYAxis(sensorChart);
                    
                    // 4. Perbarui grafik secara keseluruhan
                    sensorChart.update();

                    // 5. UPDATE TIMESTAMP
                    const timestampEl = $('#timestamp-display');
                    const d = new Date(gauges.timestamp);
                    timestampEl.html(`<strong>Data Terakhir:</strong> ${d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}`);
                    timestampEl.addClass('updated');
                    setTimeout(() => timestampEl.removeClass('updated'), 1000);
                },
                error: function(xhr) {
                    console.error("Gagal mengambil data:", xhr.responseText);
                    $('#dashboard-content').html('<div class="alert-error"><strong>Gagal Memuat Data.</strong> Periksa koneksi atau endpoint API. Mencoba lagi...</div>');
                }
            });
        }

        $(document).ready(function() {
            fetchDataAndUpdate();
            setInterval(fetchDataAndUpdate, 15000); 
        });
    </script>
</body>
</html>
