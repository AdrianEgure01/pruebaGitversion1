<?php
include 'db_connection.php';
require_once __DIR__ . '/../fpdf186/fpdf.php'; // Incluye la biblioteca FPDF

// Manejar la búsqueda de productos
if (isset($_GET['buscar'])) {
    $buscar = $_GET['buscar'];
    $sql_buscar = "SELECT * FROM productos WHERE nombre LIKE :buscar";
    $stmt_buscar = $pdo->prepare($sql_buscar);
    $stmt_buscar->execute(['buscar' => "%$buscar%"]);
    $productos = $stmt_buscar->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Si no hay búsqueda, cargar todos los productos
    $sql_todos = "SELECT * FROM productos";
    $stmt_todos = $pdo->prepare($sql_todos);
    $stmt_todos->execute();
    $productos = $stmt_todos->fetchAll(PDO::FETCH_ASSOC);
}

// Manejar la adición al pedido
session_start();
if (!isset($_SESSION['pedido'])) {
    $_SESSION['pedido'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $accion = $_POST['accion'];
    if ($accion === 'agregar') {
        $producto_id = $_POST['producto_id'];
        $producto_nombre = $_POST['producto_nombre'];
        $cantidad = (int)$_POST['cantidad'];
        $precio = (float)$_POST['precio'];

        if ($cantidad > 0) {
            $_SESSION['pedido'][] = [
                'id' => $producto_id,
                'nombre' => $producto_nombre,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'total' => $cantidad * $precio,
            ];
        }
    } elseif ($accion === 'procesar') {
        try {
            $pdo->beginTransaction();
            foreach ($_SESSION['pedido'] as $item) {
                // Actualizar la cantidad en la base de datos
                $sql_update = "UPDATE productos SET cantidad = cantidad - :cantidad WHERE id = :id AND cantidad >= :cantidad";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([
                    'cantidad' => $item['cantidad'],
                    'id' => $item['id'],
                ]);

                if ($stmt_update->rowCount() === 0) {
                    throw new Exception("No hay suficiente inventario para el producto: " . $item['nombre']);
                }

                // Insertar en la tabla de pedidos
                $sql_pedido = "INSERT INTO pedidos (producto_id, cantidad, total) VALUES (:producto_id, :cantidad, :total)";
                $stmt_pedido = $pdo->prepare($sql_pedido);
                $stmt_pedido->execute([
                    'producto_id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'total' => $item['total'],
                ]);
            }
            $pdo->commit();
            $_SESSION['pedido'] = [];
            $mensaje = "Pedido procesado correctamente.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "Error al procesar el pedido: " . $e->getMessage();
        }
    } elseif ($accion === 'cancelar') {
        $_SESSION['pedido'] = [];
        $mensaje = "Pedido cancelado.";
    }
}

// ** Generar PDF del Pedido Actual **
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'descargar_pdf') {
    class PDF extends FPDF {
        // Encabezado del documento
        function Header() {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 10, 'NOTA DE PEDIDO', 0, 1, 'C');
            $this->Ln(5);
        }

        // Pie de página
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial', 'I', 8);
            $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
        }
    }

    // Crear el PDF
    $pdf = new PDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 12);

    // Mostrar datos del cliente en el PDF
    $cliente_nombre = $_SESSION['cliente']['nombre'] ?? 'Desconocido';
    $cliente_dni_ruc = $_SESSION['cliente']['dni_ruc'] ?? 'Desconocido';
    
    $pdf->Cell(0, 10, 'Cliente: ' . $cliente_nombre, 0, 1);
    $pdf->Cell(0, 10, 'DNI/RUC: ' . $cliente_dni_ruc, 0, 1);
    $pdf->Ln(10);

    // Mostrar los productos del pedido
    $pdf->Cell(60, 10, 'Nombre', 1);
    $pdf->Cell(30, 10, 'Cantidad', 1);
    $pdf->Cell(30, 10, 'Precio Unitario', 1);
    $pdf->Cell(30, 10, 'Total', 1);
    $pdf->Ln();

    $total_pedido = 0;
    foreach ($_SESSION['pedido'] as $item) {
        $pdf->Cell(60, 10, $item['nombre'], 1);
        $pdf->Cell(30, 10, $item['cantidad'], 1);
        $pdf->Cell(30, 10, number_format($item['precio'], 2), 1);
        $pdf->Cell(30, 10, number_format($item['total'], 2), 1);
        $pdf->Ln();
        $total_pedido += $item['total'];
    }

    $pdf->Ln(10);
    $pdf->Cell(120, 10, 'Total del Pedido:', 0, 0, 'R');
    $pdf->Cell(40, 10, number_format($total_pedido, 2), 0, 1, 'R');

    $pdf->Output('D', 'Pedido.pdf');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Gestión de Pedidos</h2>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <!-- Formulario de cliente -->
    <h3>Datos del Cliente</h3>
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="cliente_nombre" class="form-label">Nombre del Cliente</label>
            <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" required>
        </div>
        <div class="mb-3">
            <label for="cliente_dni_ruc" class="form-label">DNI o RUC</label>
            <input type="text" class="form-control" id="cliente_dni_ruc" name="cliente_dni_ruc" required>
        </div>
        <button type="submit" name="accion" value="guardar_cliente" class="btn btn-primary">Guardar Datos del Cliente</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'guardar_cliente') {
        // Guardar datos del cliente en la sesión
        $_SESSION['cliente'] = [
            'nombre' => $_POST['cliente_nombre'],
            'dni_ruc' => $_POST['cliente_dni_ruc'],
        ];
    }
    ?>

    <!-- Buscar productos -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="buscar" class="form-control" placeholder="Buscar productos" value="<?php echo $_GET['buscar'] ?? ''; ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="http://localhost/gestion_pedidos2.0/" class="btn btn-secondary">Regresar al Menú</a>
        </div>  
    </form>

    <!-- Mostrar productos -->
    <h3>Productos Disponibles:</h3>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Nombre</th>
            <th>Precio</th>
            <th>Cantidad</th>
            <th>Acciones</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($productos as $producto): ?>
            <tr>
                <td><?php echo $producto['nombre']; ?></td>
                <td><?php echo $producto['precio']; ?></td>
                <td><?php echo $producto['cantidad']; ?></td>
                <td>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                        <input type="hidden" name="producto_nombre" value="<?php echo $producto['nombre']; ?>">
                        <input type="hidden" name="precio" value="<?php echo $producto['precio']; ?>">
                        <div class="input-group">
                            <input type="number" name="cantidad" class="form-control" placeholder="Cantidad">
                            <button type="submit" name="accion" value="agregar" class="btn btn-success">Agregar</button>
                        </div>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Mostrar el pedido actual -->
    <?php if (!empty($_SESSION['pedido'])): ?>
        <h3>Pedido Actual:</h3>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            <?php 
            $total_pedido = 0; // Inicializar total del pedido
            foreach ($_SESSION['pedido'] as $item): 
                $total_pedido += $item['total'];
            ?>
                <tr>
                    <td><?php echo $item['nombre']; ?></td>
                    <td><?php echo $item['cantidad']; ?></td>
                    <td><?php echo $item['precio']; ?></td>
                    <td><?php echo $item['total']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total del Pedido:</th>
                    <th><?php echo $total_pedido; ?></th>
                </tr>
            </tfoot>
        </table>

        <form method="POST">
            <button type="submit" name="accion" value="procesar" class="btn btn-primary">Procesar Pedido</button>
            <button type="submit" name="accion" value="cancelar" class="btn btn-danger">Cancelar Pedido</button>
        </form>
        <form method="POST">
            <button type="submit" name="accion" value="descargar_pdf" class="btn btn-warning mt-3">Descargar PDF</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
