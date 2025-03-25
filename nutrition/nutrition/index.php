<?php require_once('header.php'); ?>
<?php include_once('error.php'); ?>
<?php


$plan_generated = false;

if(isset($_POST['submit_person'])){
    $height = htmlspecialchars($_POST['height']);
    $weight = htmlspecialchars($_POST['weight']);
    $sex = htmlspecialchars($_POST['sex']);
    $goal = htmlspecialchars($_POST['goal']);
    
    // Calcular calorías recomendadas según el objetivo
    $bmr = ($sex == 'male') ? (88.36 + (13.4 * $weight) + (4.8 * $height) - (5.7 * 25)) : (447.6 + (9.2 * $weight) + (3.1 * $height) - (4.3 * 25));
    
    switch($goal) {
        case 'lose':
            $calories = $bmr - 500;
            break;
        case 'gain':
            $calories = $bmr + 500;
            break;
        case 'maintain':
        default:
            $calories = $bmr;
            break;
    }
    
    echo "<h2>Recommended Daily Caloric Intake: $calories kcal</h2>";

    // Generar plan de comidas basado en los alimentos almacenados
    $query = "SELECT name, calories FROM food_plan ORDER BY RAND()";
    $result = $con->query($query);
    
    if ($result->num_rows > 0) {
        echo "<h3>Suggested Meal Plan:</h3>";
        echo "<ul>";
        
        $total_calories = 0;
        
        while ($row = $result->fetch_assoc()) {
            if ($total_calories >= $calories) break;
            
            $food_name = $row['name'];
            $food_calories = $row['calories'];
            
            echo "<li>$food_name - $food_calories kcal</li>";
            $total_calories += $food_calories;
        }
        
        echo "</ul>";
        echo "<p><strong>Total Calories:</strong> $total_calories kcal</p>";
        $plan_generated = true;
    } else {
        echo "<p>No food items found in the database.</p>";
    }
}
?>

<h1>Enter your details</h1>
<form action="" method="post">
    <div class="form-group">
        <label for="height">Height (cm):</label>
        <input type="number" id="height" name="height" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="weight">Weight (kg):</label>
        <input type="number" id="weight" name="weight" class="form-control" required>
    </div>
    <div class="form-group">
        <label for="sex">Sex:</label>
        <select id="sex" name="sex" class="form-control" required>
            <option value="male">Male</option>
            <option value="female">Female</option>
        </select>
    </div>
    <div class="form-group">
        <label for="goal">Diet Goal:</label>
        <select id="goal" name="goal" class="form-control" required>
            <option value="lose">Lose Weight</option>
            <option value="gain">Gain Muscle</option>
            <option value="maintain">Maintain Weight</option>
        </select>
    </div>
    <button type="submit" name="submit_person" class="btn btn-primary">Calculate</button>
</form>

<?php require_once('footer.php'); ?>
