<?php
require_once('header.php');
require_once('error.php');

// Mostrar errores de MySQLi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Verificar conexión
if (!isset($con) || $con->connect_error) {
    die('Error en conexión: ' . ($con->connect_error ?? 'objeto $con no definido'));
}

// Parámetros de alerta
$stock_threshold = 100;
$days_to_alert   = 3;

// --- ELIMINAR: si viene ?delete=ID
if (isset($_GET['delete'])) {
    $del_id = (int) $_GET['delete'];
    $stmt = $con->prepare("DELETE FROM food_plan WHERE id = ?");
    $stmt->bind_param('i', $del_id);
    $stmt->execute();
    $stmt->close();
    // redirige para evitar reenvíos
    header("Location: index.php?deleted=1");
    exit;
}

// --- MODO EDICIÓN: cargar datos si viene ?edit=ID
$edit_mode = false;
$edit_id   = null;
$edit_data = [];
if (isset($_GET['edit'])) {
    $edit_id   = (int) $_GET['edit'];
    $stmt      = $con->prepare("SELECT * FROM food_plan WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $res_edit  = $stmt->get_result();
    if ($res_edit->num_rows === 1) {
        $edit_data = $res_edit->fetch_assoc();
        $edit_mode = true;
    }
    $stmt->close();
}

// --- Procesar POST: INSERT o UPDATE según entry_id
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar formulario
    $food        = htmlspecialchars($_POST['food']);
    $category    = htmlspecialchars($_POST['category']);
    $expiry      = $_POST['expiry'];
    $amount      = (float) $_POST['amount'];
    $measurement = htmlspecialchars($_POST['measure']);
    $measureNum  = (float) $_POST['measure'];
    $total       = $measureNum * $amount;
    $date_enter  = date('Y-m-d');

    // Llamada API USDA (1 solo resultado)
    $api_url = 'https://api.nal.usda.gov/fdc/v1/foods/search';
    $params  = ['query' => $food, 'pageSize' => 1, 'api_key' => apikey()];
    $ch      = curl_init("{$api_url}?" . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resp    = curl_exec($ch);
    curl_close($ch);
    $result  = json_decode($resp, true);

    // Inicializar nutrientes
    $nutrients = array_fill_keys([
        'calories','protein','fat','carbohydrates',
        'fiber','sugars','cholesterol','sodium',
        'vitamin_c','calcium','iron','potassium',
        'magnesium','nitrogen'
    ], 0.0);

    if (!empty($result['foods'][0]['foodNutrients'])) {
        foreach ($result['foods'][0]['foodNutrients'] as $n) {
            $val = (float) $n['value'];
            switch ($n['nutrientName']) {
                case 'Energy':                          if ($n['unitName']==='KCAL') $nutrients['calories'] = $val; break;
                case 'Protein':                         if ($n['unitName']==='G')    $nutrients['protein']  = $val; break;
                case 'Total lipid (fat)':               if ($n['unitName']==='G')    $nutrients['fat']      = $val; break;
                case 'Carbohydrate, by difference':     if ($n['unitName']==='G')    $nutrients['carbohydrates'] = $val; break;
                case 'Fiber, total dietary':            if ($n['unitName']==='G')    $nutrients['fiber']    = $val; break;
                case 'Sugars, total including NLEA':    if ($n['unitName']==='G')    $nutrients['sugars']   = $val; break;
                case 'Cholesterol':                     if ($n['unitName']==='MG')   $nutrients['cholesterol'] = $val; break;
                case 'Sodium, Na':                      if ($n['unitName']==='MG')   $nutrients['sodium']   = $val; break;
                case 'Vitamin C, total ascorbic acid':  if ($n['unitName']==='MG')   $nutrients['vitamin_c'] = $val; break;
                case 'Calcium, Ca':                     if ($n['unitName']==='MG')   $nutrients['calcium'] = $val; break;
                case 'Iron, Fe':                        if ($n['unitName']==='MG')   $nutrients['iron']    = $val; break;
                case 'Potassium, K':                    if ($n['unitName']==='MG')   $nutrients['potassium'] = $val; break;
                case 'Magnesium':                       if ($n['unitName']==='MG')   $nutrients['magnesium'] = $val; break;
                case 'Nitrogen':                        $nutrients['nitrogen'] = $val; break;
            }
        }

        // Construir SQL
        if (!empty($_POST['entry_id'])) {
            // UPDATE
            $id  = (int) $_POST['entry_id'];
            $sql = "UPDATE food_plan SET
                name=?, category=?, measurement=?, amount=?, total=?,
                calories=?, protein=?, fat=?, carbohydrates=?, fiber=?,
                sugars=?, cholesterol=?, sodium=?, vitamin_c=?, calcium=?,
                iron=?, potassium=?, magnesium=?, nitrogen=?,
                expiry_date=? 
              WHERE id=?";
        } else {
            // INSERT
            $sql = "INSERT INTO food_plan (
                name, category, measurement,
                amount, total,
                calories, protein, fat, carbohydrates, fiber,
                sugars, cholesterol, sodium, vitamin_c, calcium,
                iron, potassium, magnesium, nitrogen,
                expiry_date, date_entered
            ) VALUES (
                ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?
            )";
        }
        $stmt = $con->prepare($sql);
        if (!$stmt) die('Prepare failed: '.$con->error);

        // Tipos y bind
        if (!empty($_POST['entry_id'])) {
            $types = str_repeat('s', 3) . str_repeat('d', 16) . 's' . 'i';
            $params = [
              $food, $category, $measurement,
              $amount, $total,
              $nutrients['calories'], $nutrients['protein'], $nutrients['fat'], $nutrients['carbohydrates'], $nutrients['fiber'],
              $nutrients['sugars'], $nutrients['cholesterol'], $nutrients['sodium'], $nutrients['vitamin_c'], $nutrients['calcium'],
              $nutrients['iron'], $nutrients['potassium'], $nutrients['magnesium'], $nutrients['nitrogen'],
              $expiry,
              $id
            ];
        } else {
            $types = str_repeat('s', 3) . str_repeat('d', 16) . str_repeat('s', 2);
            $params = [
              $food, $category, $measurement,
              $amount, $total,
              $nutrients['calories'], $nutrients['protein'], $nutrients['fat'], $nutrients['carbohydrates'], $nutrients['fiber'],
              $nutrients['sugars'], $nutrients['cholesterol'], $nutrients['sodium'], $nutrients['vitamin_c'], $nutrients['calcium'],
              $nutrients['iron'], $nutrients['potassium'], $nutrients['magnesium'], $nutrients['nitrogen'],
              $expiry, $date_enter
            ];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();

        echo $id
            ? "<div class='alert alert-info'>Entrada #{$id} actualizada.</div>"
            : "<div class='alert alert-success'>Donación registrada correctamente.</div>";
    }
}

// --- Obtener inventario actualizado ---
$res = $con->query("
  SELECT *, DATEDIFF(expiry_date, CURDATE()) AS days_left
  FROM food_plan
  ORDER BY expiry_date ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Inventario Banco de Alimentos</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container py-4">

  <!-- Mensaje de eliminación -->
  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">
      Donación eliminada correctamente.
    </div>
  <?php endif; ?>

  <!-- Formulario (ADD / EDIT) -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <?= $edit_mode ? "Editar Donación #{$edit_id}" : "Registrar Donación" ?>
    </div>
    <div class="card-body">
      <form method="post">
        <?php if ($edit_mode): ?>
          <input type="hidden" name="entry_id" value="<?= $edit_id ?>">
        <?php endif; ?>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="food">Alimento</label>
            <input list="food-list" id="food" name="food" class="form-control"
                   autocomplete="off" placeholder="Ej. manzana" required
                   value="<?= $edit_data['name'] ?? '' ?>">
            <datalist id="food-list"></datalist>
          </div>
          <div class="form-group col-md-2">
            <label for="category">Categoría</label>
            <select id="category" name="category" class="form-control">
              <?php foreach (['perecedero','no perecedero'] as $cat): ?>
                <option value="<?= $cat ?>"
                  <?= (isset($edit_data['category']) && $edit_data['category']===$cat) ? 'selected' : '' ?>>
                  <?= ucfirst($cat) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-2">
            <label for="expiry">Caducidad</label>
            <input type="date" id="expiry" name="expiry" class="form-control" required
                   value="<?= $edit_data['expiry_date'] ?? '' ?>">
          </div>
          <div class="form-group col-md-2">
            <label for="amount">Cantidad</label>
            <input type="number" id="amount" step="0.1" name="amount"
                   class="form-control" placeholder="1" required
                   value="<?= $edit_data['amount'] ?? '' ?>">
          </div>
          <div class="form-group col-md-2">
            <label for="measure">Medida (g)</label>
            <select id="measure" name="measure" class="form-control">
              <?php foreach ([1000=>'Kilo',240=>'Taza',120=>'1/2 Taza',38=>'Rebanada'] as $val=>$txt): ?>
                <option value="<?= $val ?>"
                  <?= (isset($edit_data['measurement']) && $edit_data['measurement']==$val) ? 'selected' : '' ?>>
                  <?= "{$txt} ({$val}g)" ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button type="submit" class="btn <?= $edit_mode ? 'btn-info' : 'btn-success' ?>">
          <?= $edit_mode ? 'Actualizar' : 'Agregar Donación' ?>
        </button>
        <?php if ($edit_mode): ?>
          <a href="index.php" class="btn btn-secondary ml-2">Cancelar</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Inventario -->
  <div class="card">
    <div class="card-header">Inventario</div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Alimento</th>
              <th>Cat.</th>
              <th>Ingreso</th>
              <th>Caduca</th>
              <th>Cant.</th>
              <th>Medida</th>
              <th>Stock (g)</th>
              <th>Días Rest.</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($r = $res->fetch_assoc()): 
                $cls = '';
                if ($r['days_left'] <= $days_to_alert) $cls = 'table-warning';
                if ($r['total']     <= $stock_threshold) $cls = 'table-danger';
            ?>
              <tr class="<?= $cls ?>">
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><?= htmlspecialchars($r['category']) ?></td>
                <td><?= $r['date_entered'] ?></td>
                <td><?= $r['expiry_date'] ?></td>
                <td><?= $r['amount'] ?></td>
                <td><?= $r['measurement'] ?></td>
                <td><?= $r['total'] ?></td>
                <td><?= $r['days_left'] ?></td>
                <td>
                  <a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                  <a href="?delete=<?= $r['id'] ?>"
                     class="btn btn-sm btn-outline-danger ml-1"
                     onclick="return confirm('¿Seguro que quieres eliminar la donación #<?= $r['id'] ?>?');">
                    Eliminar
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /.container -->

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Autocomplete
document.addEventListener('DOMContentLoaded', () => {
  const input = document.getElementById('food');
  const list  = document.getElementById('food-list');
  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 3) return;
    timer = setTimeout(async () => {
      const resp = await fetch(`?search=${encodeURIComponent(q)}`);
      const suggestions = await resp.json();
      list.innerHTML = '';
      suggestions.forEach(name => {
        const option = document.createElement('option');
        option.value = name;
        list.appendChild(option);
      });
    }, 300);
  });
});
</script>
</body>
</html>
