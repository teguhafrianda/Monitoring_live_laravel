<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Day Yotta</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/yotta.png') }}">
    <style>
        :root {
            --gauge-bg-color: #e2e8f0; --gauge-inner-color: #ffffff; --text-color-primary: #1a202c;
            --text-color-secondary: #718096; --color-temp: #ef4444; --color-humidity: #3b82f6;
            --color-ph: #8b5cf6; --color-nitro: #16a34a; --color-other: #64748b;
            --color-conduc: #eab308; --color-salinity: #06b6d4; --color-tds: #6366f1; --color-par: #84cc16;
        }
        body { font-family: 'Roboto', sans-serif; background-color: #f0f2f5; color: #333; display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; padding: 1rem 2rem; box-sizing: border-box; }
        .container { width: 100%; max-width: 1400px; }
        .header { text-align: center; margin-bottom: 2rem; }
        .header h1 { font-weight: 700; color: var(--text-color-primary); }
        .header p { color: #666; font-size: 1.1rem; }
        .header .timestamp { font-size: .9rem; color: #555; background-color: #e2e8f0; display: inline-block; padding: 8px 15px; border-radius: 20px; margin-top: 10px; min-height: 24px; font-weight: 500; transition: background-color 0.3s ease; }
        .header .timestamp.updated { background-color: #dcfce7; color: #166534; }
        
        /* --- PERUBAHAN LAYOUT GAUGE --- */
        .gauge-grid {
            display: grid;
            /* 6 kolom pada layar besar untuk membuat 2 baris */
            grid-template-columns: repeat(6, 1fr);
            gap: 1.5rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        /* Media query untuk membuat layout responsif di layar kecil */
        @media (max-width: 1200px) { .gauge-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (max-width: 768px) { .gauge-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 480px) { .gauge-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem;} }

        .gauge-wrapper { display: flex; flex-direction: column; align-items: center; }
        .gauge-circle { display: grid; place-items: center; width: 100%; aspect-ratio: 1 / 1; border-radius: 50%; background: conic-gradient(var(--color, #3b82f6) calc(var(--p, 0) * 1%), var(--gauge-bg-color) 0); transition: background 0.5s ease-in-out; }
        .gauge-inner { display: flex; flex-direction: column; justify-content: center; align-items: center; width: 85%; height: 85%; background: var(--gauge-inner-color); border-radius: 50%; box-shadow: inset 0 0 15px rgba(0,0,0,0.05); }
        .gauge-value { font-size: clamp(1.4rem, 4vw, 2.1rem); font-weight: 700; color: var(--text-color-primary); line-height: 1; }
        .gauge-value small { font-size: 0.8rem; font-weight: 500; margin-left: 2px; }
        .gauge-label { font-size: clamp(0.8rem, 2vw, 0.9rem); font-weight: 400; color: var(--text-color-secondary); margin-top: 8px; text-align: center; padding: 0 5px; }
        .chart-wrapper { background-color: #fff; padding: 1.5rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.07); }
        .loader-container { text-align: center; padding: 5rem 0; }
        .loader { border: 8px solid #f3f3f3; border-radius: 50%; border-top: 8px solid #3b82f6; width: 60px; height: 60px; animation: spin 1.5s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .alert-error { text-align: center; padding: 1.5rem; border-radius: 8px; background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    </style>
</head>
<body>
<div class="container" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
    <div class="header" style="text-align: center; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: center; align-items: center; gap: 3rem; margin-bottom: 1.5rem;">
            <img src="{{ asset('assets/images/logoum.png') }}" alt="Logo UM" style="height: 70px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
            <img src="{{ asset('assets/images/logotrans.png') }}" alt="Logo LPPM" style="height: 70px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
        </div>
        <h1>Live Dashboard Monitoring Sensor</h1>
<p><strong>Universitas Negeri Malang - LPPM</strong></p>
<p>Data diperbarui otomatis setiap 15 detik...</p>

        <div class="timestamp" id="timestamp-display"
            style="display: inline-block; background-color: #edf2f7; color: #2d3748;
                   padding: 0.5rem 1rem; border-radius: 9999px; margin-top: 1rem;
                   font-weight: 500; transition: background-color 0.3s ease;">
            Menunggu data...
        </div>
    </div>

    <div id="dashboard-content" style="margin-top: 2rem;">
        <div class="loader-container" id="loader" style="text-align: center; padding: 4rem 0;">
            <div class="loader" style="border: 8px solid #e2e8f0; border-top: 8px solid #4299e1;
                                       border-radius: 50%; width: 60px; height: 60px;
                                       animation: spin 1.5s linear infinite; margin: 0 auto;">
            </div>
            <p style="margin-top: 1rem; color: #718096;">Memuat data awal...</p>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>


    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    <script>
        let sensorChart;
        const SENSOR_RANGES = {
            default: { min: 0, max: 100 }, air_temperature: { min: 0, max: 50 }, temperature: { min: 0, max: 50 },
            humidity: { min: 0, max: 100 }, air_humidity: { min: 0, max: 100 }, ph: { min: 0, max: 14 },
            nitrogen: { min: 0, max: 250 }, phosphorus: { min: 0, max: 250 }, potassium: { min: 0, max: 250 },
            conductivity: { min: 0, max: 2000 }, salinity: { min: 0, max: 1000 }, tds: { min: 0, max: 1000 }, par: { min: 0, max: 2000 }
        };

        function createDashboardElements() { const content = `<div class="gauge-grid" id="gauge-container"></div> <div class="chart-wrapper"><canvas id="sensorChart"></canvas></div>`; $('#dashboard-content').html(content); }
        
        function initializeChart() {
            const ctx = document.getElementById('sensorChart').getContext('2d');
            sensorChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        { label: 'Suhu Udara (°C)', data: [], borderColor: 'var(--color-temp)', backgroundColor: 'rgba(239, 68, 68, 0.1)', tension: 0.4, fill: true },
                        { label: 'Kelembaban Udara (%)', data: [], borderColor: 'var(--color-humidity)', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.4, fill: true },
                        { label: 'Suhu Tanah (°C)', data: [], borderColor: '#f97316', backgroundColor: 'rgba(249, 115, 22, 0.1)', tension: 0.4, fill: true, hidden: true },
                        { label: 'Kelembaban Tanah (%)', data: [], borderColor: '#22c55e', backgroundColor: 'rgba(34, 197, 94, 0.1)', tension: 0.4, fill: true, hidden: true },
                        { label: 'Konduktivitas (µS/cm)', data: [], borderColor: 'var(--color-conduc)', backgroundColor: 'rgba(234, 179, 8, 0.1)', tension: 0.4, fill: true, hidden: true },
                        { label: 'pH Tanah', data: [], borderColor: 'var(--color-ph)', backgroundColor: 'rgba(139, 92, 246, 0.1)', tension: 0.4, fill: true, hidden: true },
                        { label: 'Nitrogen (mg/kg)', data: [], borderColor: 'var(--color-nitro)', backgroundColor: 'rgba(22, 163, 74, 0.1)', tension: 0.4, fill: true, hidden: true },
                        { label: 'Fosfor (mg/kg)', data: [], borderColor: '#d946ef', backgroundColor: 'rgba(217, 70, 239, 0.1)', tension: 0.4, fill: true, hidden: true },
                        { label: 'Kalium (mg/kg)', data: [], borderColor: '#f43f5e', backgroundColor: 'rgba(244, 63, 94, 0.1)', tension: 0.4, fill: true, hidden: true },
                        { label: 'Salinitas (ppt)', data: [], borderColor: 'var(--color-salinity)', backgroundColor: 'rgba(6, 182, 212, 0.1)', tension: 0.4, fill: true, hidden: true },
                        { label: 'TDS (ppm)', data: [], borderColor: 'var(--color-tds)', backgroundColor: 'rgba(99, 102, 241, 0.1)', tension: 0.4, fill: true, hidden: true },
                        { label: 'PAR (μmol/m²/s)', data: [], borderColor: 'var(--color-par)', backgroundColor: 'rgba(132, 204, 22, 0.1)', tension: 0.4, fill: true, hidden: true }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, borderWidth: 2,
                    scales: { y: {} },
                    plugins: { legend: { position: 'top', onClick: (e, legendItem, legend) => { Chart.defaults.plugins.legend.onClick(e, legendItem, legend); updateYAxis(legend.chart); }}, title: { display: true, text: 'Grafik Tren Sensor (30 Data Terakhir)', font: { size: 16 } } },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
        }

        function calculatePercentage(value, type) { const range = SENSOR_RANGES[type] || SENSOR_RANGES.default; if (value === null || isNaN(parseFloat(value))) return 0; let val = parseFloat(value); val = Math.max(range.min, Math.min(range.max, val)); const percentage = ((val - range.min) / (range.max - range.min)) * 100; return percentage.toFixed(2); }
        function createGaugeHTML(type, value, unit, label) { const percentage = calculatePercentage(value, type); const displayValue = (value !== null && !isNaN(parseFloat(value))) ? parseFloat(value).toFixed(1) : 'N/A'; const displayUnit = (displayValue !== 'N/A') ? `<small>${unit}</small>` : ''; let colorVar = '--color-other'; if (type.includes('temp')) colorVar = '--color-temp'; else if (type.includes('humi')) colorVar = '--color-humidity'; else if (type === 'ph') colorVar = '--color-ph'; else if (type === 'nitrogen') colorVar = '--color-nitro'; else if (type === 'conductivity') colorVar = '--color-conduc'; else if (type === 'salinity') colorVar = '--color-salinity'; else if (type === 'tds') colorVar = '--color-tds'; else if (type === 'par') colorVar = '--color-par'; return `<div class="gauge-wrapper"><div class="gauge-circle" style="--p:${percentage}; --color:var(${colorVar})"><div class="gauge-inner"><div class="gauge-value">${displayValue}${displayUnit}</div></div></div><div class="gauge-label">${label}</div></div>`; }
        
        // --- PERUBAHAN PADDING SUMBU Y ---
        function updateYAxis(chart) { 
    let allVisibleData = []; 
    chart.data.datasets.forEach((dataset, index) => { 
        if (chart.isDatasetVisible(index)) { 
            allVisibleData.push(...dataset.data); 
        } 
    }); 
    allVisibleData = allVisibleData.filter(v => v !== null && v !== undefined); 
    if (allVisibleData.length === 0) { return; } 
    const dataMin = Math.min(...allVisibleData); 
    const dataMax = Math.max(...allVisibleData); 

    // Paksa minimum rentang y setidaknya 50 supaya selalu ada ruang
    let yMin = dataMin - 10;
    let yMax = dataMax + 10;

    if (yMax - yMin < 50) {
        const mid = (yMax + yMin) / 2;
        yMin = mid - 25;
        yMax = mid + 25;
    }

    chart.options.scales.y.min = Math.floor(yMin);
    chart.options.scales.y.max = Math.ceil(yMax);
}


        function fetchDataAndUpdate() {
            $.ajax({
                url: '/api/sensor-data', type: 'GET', dataType: 'json',
                success: function(response) {
                    if ($('#loader').length) { createDashboardElements(); initializeChart(); }
                    const gauges = response.latestReading;
                    if (!gauges) return;
                    const gaugeContainer = $('#gauge-container');
                    gaugeContainer.html('');
                    gaugeContainer.append(createGaugeHTML('air_temperature', gauges.air_temperature, '°C', 'Suhu Udara'));
                    gaugeContainer.append(createGaugeHTML('air_humidity', gauges.air_humidity, '%', 'Kelembaban Udara'));
                    gaugeContainer.append(createGaugeHTML('temperature', gauges.temperature, '°C', 'Suhu Tanah'));
                    gaugeContainer.append(createGaugeHTML('humidity', gauges.humidity, '%', 'Kelembaban Tanah'));
                    gaugeContainer.append(createGaugeHTML('conductivity', gauges.conductivity, 'µS/cm', 'Konduktivitas'));
                    gaugeContainer.append(createGaugeHTML('ph', gauges.ph, '', 'pH Tanah'));
                    gaugeContainer.append(createGaugeHTML('nitrogen', gauges.nitrogen, 'mg/kg', 'Nitrogen (N)'));
                    gaugeContainer.append(createGaugeHTML('phosphorus', gauges.phosphorus, 'mg/kg', 'Fosfor (P)'));
                    gaugeContainer.append(createGaugeHTML('potassium', gauges.potassium, 'mg/kg', 'Kalium (K)'));
                    gaugeContainer.append(createGaugeHTML('salinity', gauges.salinity, 'ppt', 'Salinitas'));
                    gaugeContainer.append(createGaugeHTML('tds', gauges.tds, 'ppm', 'TDS'));
                    gaugeContainer.append(createGaugeHTML('par', gauges.par, 'μmol', 'PAR'));
                    
                    const chartData = response.chartData;
                    sensorChart.data.labels = chartData.labels;
                    const ds = sensorChart.data.datasets;
                    const apiDs = chartData.datasets;
                    ds[0].data = apiDs.air_temperature; ds[1].data = apiDs.air_humidity; ds[2].data = apiDs.soil_temperature;
                    ds[3].data = apiDs.soil_humidity; ds[4].data = apiDs.conductivity; ds[5].data = apiDs.ph;
                    ds[6].data = apiDs.nitrogen; ds[7].data = apiDs.phosphorus; ds[8].data = apiDs.potassium;
                    ds[9].data = apiDs.salinity; ds[10].data = apiDs.tds; ds[11].data = apiDs.par;
                    
                    updateYAxis(sensorChart);
                    sensorChart.update();

                    const d = new Date(gauges.timestamp);
                    const timestampEl = $('#timestamp-display');
                    timestampEl.html(`<strong>Data Terakhir:</strong> ${d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })}`);
                    timestampEl.addClass('updated');
                    setTimeout(() => timestampEl.removeClass('updated'), 1000);
                },
                error: function(xhr) { console.error("Gagal mengambil data:", xhr.responseText); $('#dashboard-content').html('<div class="alert-error"><strong>Gagal Memuat Data.</strong> Periksa koneksi atau endpoint API. Mencoba lagi...</div>'); }
            });
        }
        $(document).ready(function() { fetchDataAndUpdate(); setInterval(fetchDataAndUpdate, 15000); });
    </script>
</body>
</html>
