<?php
session_start();

// Verificar si el usuario está registrado
if (!isset($_SESSION['usuario_registrado']) || $_SESSION['usuario_registrado'] !== true) {
    header("Location: login.php");
    exit();
}

// Include the tool management module
require_once('tool_management.php');
require_once('pedidos_functions.php');

$conn = connectDB();

$herramientas_prestadas_hoy = [];
$query_hoy = "
    SELECT
        h.nombre,
        h.codigo,
        COUNT(dp.herramienta) as total_prestamos,
        SUM(dp.cantidad) as cantidad_total
    FROM detalle_pedido dp
    JOIN herramientas h ON dp.herramienta = h.codigo
    JOIN pedidos p ON dp.id_pedido = p.id_pedido
    WHERE DATE(p.fecha_pedido) = CURDATE()
    GROUP BY h.codigo, h.nombre
    ORDER BY total_prestamos DESC
    LIMIT 10
";
$result_hoy = $conn->query($query_hoy);
if ($result_hoy->num_rows > 0) {
    while($row = $result_hoy->fetch_assoc()) {
        $herramientas_prestadas_hoy[] = $row;
    }
}


$pedidos_por_dia = [];
$query_pedidos_dia = "
    SELECT
        DATE(fecha_pedido) as fecha,
        COUNT(*) as total_pedidos,
        SUM(CASE WHEN estado = 'devuelto' THEN 1 ELSE 0 END) as pedidos_devueltos
    FROM pedidos
    WHERE fecha_pedido >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_pedido)
    ORDER BY fecha ASC
";
$result_pedidos_dia = $conn->query($query_pedidos_dia);
if ($result_pedidos_dia->num_rows > 0) {
    while($row = $result_pedidos_dia->fetch_assoc()) {
        // Calculate 'pedidos_pendientes' as total_pedidos - pedidos_devueltos
        $row['pedidos_pendientes'] = $row['total_pedidos'] - $row['pedidos_devueltos'];
        $pedidos_por_dia[] = $row;
    }
}

$herramientas_data_para_grafico = [];
$unique_tools = [];
$dates_in_period = [];

$query_historial_grafico = "
    SELECT
        DATE(p.fecha_pedido) as fecha,
        h.nombre as nombre_herramienta,
        SUM(dp.cantidad) as cantidad_prestada
    FROM detalle_pedido dp
    JOIN pedidos p ON dp.id_pedido = p.id_pedido
    JOIN herramientas h ON dp.herramienta = h.codigo
    WHERE p.fecha_pedido >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(p.fecha_pedido), h.nombre
    ORDER BY p.fecha_pedido ASC, h.nombre ASC
";
$result_historial_grafico = $conn->query($query_historial_grafico);

if ($result_historial_grafico->num_rows > 0) {
    while($row = $result_historial_grafico->fetch_assoc()) {
        $fecha = $row['fecha'];
        $nombre_herramienta = $row['nombre_herramienta'];
        $cantidad = (int)$row['cantidad_prestada'];

        if (!in_array($nombre_herramienta, $unique_tools)) {
            $unique_tools[] = $nombre_herramienta;
        }
        if (!in_array($fecha, $dates_in_period)) {
            $dates_in_period[] = $fecha;
        }

        if (!isset($herramientas_data_para_grafico[$nombre_herramienta])) {
            $herramientas_data_para_grafico[$nombre_herramienta] = [];
        }
        $herramientas_data_para_grafico[$nombre_herramienta][$fecha] = $cantidad;
    }
}

// Ensure all dates have entries for all tools, with 0 if no loans
$full_herramientas_data = [];
foreach ($unique_tools as $tool_name) {
    $full_herramientas_data[$tool_name] = [];
    foreach ($dates_in_period as $date) {
        $full_herramientas_data[$tool_name][date('d/m', strtotime($date))] = $herramientas_data_para_grafico[$tool_name][$date] ?? 0;
    }
    // Sort by date for consistent display
    ksort($full_herramientas_data[$tool_name]);
}
sort($dates_in_period); // Sort dates for consistent X-axis labels
$formatted_dates_for_chart = array_map(function($date) {
    return date('d/m', strtotime($date));
}, $dates_in_period);


// Obtener estadísticas de stock bajo (from original file)
$herramientas_stock_bajo = [];
$query_stock_bajo = "
    SELECT
        h.nombre,
        h.codigo,
        h.cantidad,
        COALESCE(SUM(dp.cantidad), 0) as cantidad_prestada,
        (h.cantidad - COALESCE(SUM(CASE WHEN p.estado = 'pendiente' THEN dp.cantidad ELSE 0 END), 0)) as disponible
    FROM herramientas h
    LEFT JOIN detalle_pedido dp ON h.codigo = dp.herramienta
    LEFT JOIN pedidos p ON dp.id_pedido = p.id_pedido
    WHERE h.cantidad < 10
    GROUP BY h.codigo, h.nombre, h.cantidad
    ORDER BY h.cantidad ASC
";
$result_stock_bajo = $conn->query($query_stock_bajo);
if ($result_stock_bajo->num_rows > 0) {
    while($row = $result_stock_bajo->fetch_assoc()) {
        $herramientas_stock_bajo[] = $row;
    }
}

// Obtener estadísticas generales (from original file)
$stats = [];
$result_total_tools = $conn->query("SELECT COUNT(*) as total FROM herramientas");
$stats['total_tools'] = $result_total_tools->fetch_assoc()['total'];

$result_total_quantity = $conn->query("SELECT SUM(cantidad) as total_quantity FROM herramientas");
$stats['total_quantity'] = $result_total_quantity->fetch_assoc()['total_quantity'] ?: 0;

$result_pedidos_mes = $conn->query("SELECT COUNT(*) as total FROM pedidos WHERE fecha_pedido >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['pedidos_mes'] = $result_pedidos_mes->fetch_assoc()['total'];

$result_pedidos_pendientes = $conn->query("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'");
$stats['pedidos_pendientes'] = $result_pedidos_pendientes->fetch_assoc()['total'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Sistema de Gestión de Pañol</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        .stat-card {
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
        .chart-container {
            position: relative;
            /* REMOVED: height: 400px; */ /* Let content define height */
            min-height: 350px; /* Optional: ensures a minimum height */
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex; /* Use flexbox for better internal alignment */
            flex-direction: column; /* Stack children vertically */
            justify-content: center; /* Center content vertically if space allows */
            align-items: center; /* Center content horizontally */
        }
        .chart-container h4 {
            flex-shrink: 0; /* Prevent title from shrinking */
            margin-bottom: 1rem; /* Add some space below the title */
        }
        .chart-container canvas {
            flex-grow: 1; /* Allow canvas to grow and take available space */
            width: 100% !important; /* Ensure canvas fills container width */
            height: auto !important; /* Allow canvas height to adjust */
            max-height: 300px; /* Optional: limit max height of canvas itself */
        }
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .history-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .history-day-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 10px;
        }
        .history-day-header {
            font-weight: bold;
            margin-bottom: 5px;
            color: #495057;
        }
        .history-item {
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>

<body class="bg-light">
    <?php include(__DIR__ . '/menu.php'); ?>

    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-5 mb-0">
                        <i class="fas fa-chart-line me-3"></i>
                        Estadísticas del Pañol
                    </h1>
                    <p class="lead mb-0 mt-2">Análisis y métricas del sistema de gestión</p>
                </div>
                <div class="col-lg-4 text-end">
                    <i class="fas fa-analytics fa-5x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white">
                    <i class="fas fa-tools stat-icon"></i>
                    <div class="stat-number"><?php echo $stats['total_tools']; ?></div>
                    <div class="stat-label">Herramientas Total</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white">
                    <i class="fas fa-boxes stat-icon"></i>
                    <div class="stat-number"><?php echo $stats['total_quantity']; ?></div>
                    <div class="stat-label">Unidades en Stock</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white">
                    <i class="fas fa-calendar-check stat-icon"></i>
                    <div class="stat-number"><?php echo $stats['pedidos_mes']; ?></div>
                    <div class="stat-label">Pedidos este Mes</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-dark">
                    <i class="fas fa-clock stat-icon"></i>
                    <div class="stat-number"><?php echo $stats['pedidos_pendientes']; ?></div>
                    <div class="stat-label">Pedidos Pendientes</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="chart-container">
                    <h4 class="text-center mb-3">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Herramientas Más Prestadas (Hoy)
                    </h4>
                    <?php if (empty($herramientas_prestadas_hoy)): ?>
                        <p class="text-center text-muted">No hay préstamos de herramientas registrados hoy.</p>
                    <?php else: ?>
                        <canvas id="herramientasChartHoy"></canvas>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="chart-container">
                    <h4 class="text-center mb-3">
                        <i class="fas fa-chart-bar text-info me-2"></i>
                        Tendencia de Pedidos por Día (Últimos 30 días)
                    </h4>
                    <?php if (empty($pedidos_por_dia)): ?>
                        <p class="text-center text-muted">No hay datos de pedidos para los últimos 30 días.</p>
                    <?php else: ?>
                        <canvas id="pedidosBarChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="chart-container">
                    <h4 class="text-center mb-3">
                        <i class="fas fa-chart-line text-primary me-2"></i>
                        Unidades Prestadas por Herramienta (Últimos 30 días)
                    </h4>
                    <?php if (empty($full_herramientas_data)): ?>
                        <p class="text-center text-muted">No hay historial de préstamos de herramientas en los últimos 30 días.</p>
                    <?php else: ?>
                        <div class="mb-3 w-50">
                            <label for="selectTool" class="form-label text-muted">Seleccionar Herramienta:</label>
                            <select class="form-select" id="selectTool">
                                <?php foreach ($unique_tools as $tool_name): ?>
                                    <option value="<?php echo addslashes($tool_name); ?>"><?php echo htmlspecialchars($tool_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <canvas id="herramientasLineChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuración global de Chart.js
        Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
        Chart.defaults.color = '#6c757d';

        // --- Gráfico de Herramientas Más Prestadas HOY ---
        <?php if (!empty($herramientas_prestadas_hoy)): ?>
        const herramientasHoyData = {
            labels: [
                <?php foreach ($herramientas_prestadas_hoy as $herramienta): ?>
                '<?php echo addslashes($herramienta['nombre']); ?>',
                <?php endforeach; ?>
            ],
            datasets: [{
                label: 'Veces Prestada Hoy',
                data: [
                    <?php foreach ($herramientas_prestadas_hoy as $herramienta): ?>
                    <?php echo $herramienta['total_prestamos']; ?>,
                    <?php endforeach; ?>
                ],
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                    '#4BC0C0', '#FF6384'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        const herramientasHoyCtx = document.getElementById('herramientasChartHoy').getContext('2d');
        new Chart(herramientasHoyCtx, {
            type: 'doughnut',
            data: herramientasHoyData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Set to false to hide the legend
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' préstamos';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>


        // --- Gráfico de Barras de Pedidos por Día (BARRAS SEPARADAS) ---
        <?php if (!empty($pedidos_por_dia)): ?>
        const pedidosBarLabels = [
            <?php foreach ($pedidos_por_dia as $pedido): ?>
            '<?php echo date('d/m', strtotime($pedido['fecha'])); ?>',
            <?php endforeach; ?>
        ];

        const pedidosBarData = {
            labels: pedidosBarLabels,
            datasets: [
                {
                    label: 'Total Pedidos',
                    data: [
                        <?php foreach ($pedidos_por_dia as $pedido): ?>
                        <?php echo $pedido['total_pedidos']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(54, 162, 235, 0.8)', // Blue for total
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Devueltos',
                    data: [
                        <?php foreach ($pedidos_por_dia as $pedido): ?>
                        <?php echo $pedido['pedidos_devueltos']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(75, 192, 192, 0.8)', // Green for returned
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Pendientes',
                    data: [
                        <?php foreach ($pedidos_por_dia as $pedido): ?>
                        <?php echo $pedido['pedidos_pendientes']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: 'rgba(255, 99, 132, 0.8)', // Red for pending
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        };

        const pedidosBarCtx = document.getElementById('pedidosBarChart').getContext('2d');
        new Chart(pedidosBarCtx, {
            type: 'bar', // Ensure it's a bar chart
            data: pedidosBarData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    x: {
                        stacked: false,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // --- Gráfico de Línea para Herramientas Prestadas (Últimos 30 días) ---
        <?php if (!empty($full_herramientas_data)): ?>
        const herramientasLineChartCtx = document.getElementById('herramientasLineChart').getContext('2d');
        const allHerramientasData = <?php echo json_encode($full_herramientas_data); ?>;
        const chartLabels = <?php echo json_encode($formatted_dates_for_chart); ?>;
        let herramientasLineChart; // Declare chart variable globally

        function generateRandomColor() {
            const r = Math.floor(Math.random() * 255);
            const g = Math.floor(Math.random() * 255);
            const b = Math.floor(Math.random() * 255);
            return `rgb(${r},${g},${b})`;
        }

        function updateHerramientasLineChart(selectedTool) {
            if (herramientasLineChart) {
                herramientasLineChart.destroy(); // Destroy previous chart instance
            }

            const dataPoints = allHerramientasData[selectedTool] || {};
            const chartData = chartLabels.map(label => dataPoints[label] || 0);

            const lineColor = generateRandomColor(); // Dynamic color for each selection

            herramientasLineChart = new Chart(herramientasLineChartCtx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: `Unidades Prestadas de ${selectedTool}`,
                        data: chartData,
                        borderColor: lineColor,
                        backgroundColor: `rgba(255, 255, 255, 0.5)`, // Light background fill
                        borderWidth: 2,
                        pointRadius: 5,
                        pointBackgroundColor: lineColor,
                        pointBorderColor: '#fff',
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: lineColor,
                        tension: 0.3, // Makes the line slightly curved
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false // Hide dataset legend as we have a dropdown
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                title: function(context) {
                                    const dateStr = context[0].label;
                                    // Assuming labels are 'dd/mm'
                                    const [day, month] = dateStr.split('/');
                                    const year = new Date().getFullYear(); // Assuming current year
                                    const date = new Date(year, month - 1, day);
                                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                                    return date.toLocaleDateString('es-ES', options);
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y + ' unidades';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Fecha'
                            },
                            grid: {
                                display: false // Hide vertical grid lines
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Cantidad Prestada'
                            },
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1 // Ensure integer steps for quantity
                            }
                        }
                    }
                }
            });
        }

        // Initial chart load
        document.addEventListener('DOMContentLoaded', function() {
            const selectToolElement = document.getElementById('selectTool');
            if (selectToolElement && selectToolElement.options.length > 0) {
                updateHerramientasLineChart(selectToolElement.value);
            }
        });

        // Event listener for dropdown change
        document.getElementById('selectTool').addEventListener('change', function() {
            updateHerramientasLineChart(this.value);
        });
        <?php endif; ?>
    </script>
</body>
</html>