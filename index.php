<?php
require_once('header.php');
require_once('error.php');


if(isset($_POST['submit'])){
  $food = htmlspecialchars($_POST['food']);
  $amount = htmlspecialchars($_POST['amount']);
  $measure = htmlspecialchars($_POST['measure']);
  $total=$measure * $amount;
      $api_url = 'https://api.nal.usda.gov/fdc/v1/foods/search';
      $api_key = apikey();

      $data = [
          'query' => $food,
          'pageSize' => 1,
          'api_key' => $api_key
      ];

      $ch = curl_init($api_url . '?' . http_build_query($data));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $response = curl_exec($ch);
      curl_close($ch);

      $result = json_decode($response, true);
	  
	  echo "En $total g de $food hay:<br>";

      if (isset($result['foods'][0]['foodNutrients'])) {
      $calories = $protein = $fat = $carbohydrates = $fiber = $sugars = null;
          $cholesterol = $sodium = $vitamin_c = $calcium = $iron = null;
          $vitamin_b12 = $potassium = $magnesium = $nitrogen = $alcohol = null;

        foreach ($result['foods'][0]['foodNutrients'] as $nutrient) {
        $value = $nutrient['value'];
        // Debug output to identify nutrient names and units
              //echo " {$nutrient['nutrientName']}, Value: {$nutrient['value']} {$nutrient['unitName']}<br>";
        switch ($nutrient['nutrientName']) {
                  case 'Potassium, K':
                      if ($nutrient['unitName'] === 'MG') {
                          $potassium = $value;
                          echo "The Potassium content for $food is $value milligrams.<br>";
                      }
                      break;
                  case 'Magnesium':
                      if ($nutrient['unitName'] === 'MG') {
                          $magnesium = $value;
                          echo "The Magnesium content for $food is $value milligrams.<br>";
                      }
                      break;
                  case 'Water':
                      if ($nutrient['unitName'] === 'G') {
                          $water = $value;
                          echo "The water content for $food is $value grams.<br>";
                      }
                      break;
                  case 'Energy':
                      if ($nutrient['unitName'] === 'KCAL') {
                          $calories = $value;
                          echo "The calorie count for $food is $value calories.<br>";
                      }
                      break;
                  case 'Protein':
                      if ($nutrient['unitName'] === 'G') {
                          $protein = $value;
                          echo "The protein content for $food is $value grams.<br>";
                      }
                      break;
                  case 'Total lipid (fat)':
                      if ($nutrient['unitName'] === 'G') {
                          $fat = $value;
                          echo "The fat content for $food is $value grams.<br>";
                      }
                      break;
                  case 'Carbohydrate, by difference':
                      if ($nutrient['unitName'] === 'G') {
                          $carbohydrates = $value;
                          echo "The carbohydrate content for $food is $value grams.<br>";
                      }
                      break;
                  case 'Fiber, total dietary':
                      if ($nutrient['unitName'] === 'G') {
                          $fiber = $value;
                          echo "The fiber content for $food is $value grams.<br>";
                      }
                      break;
                  case 'Sugars, total including NLEA':
                      if ($nutrient['unitName'] === 'G') {
                          $sugars = $value;
                          echo "The sugar content for $food is $value grams.<br>";
                      }
                      break;
                  case 'Cholesterol':
                      if ($nutrient['unitName'] === 'MG') {
                          $cholesterol = $value;
                          echo "The cholesterol content for $food is $value milligrams.<br>";
                      }
                      break;
                  case 'Sodium, Na':
                      if ($nutrient['unitName'] === 'MG') {
                          $sodium = $value;
                          echo "The sodium content for $food is $value milligrams.<br>";
                      }
                      break;
                  case 'Vitamin C, total ascorbic acid':
                      if ($nutrient['unitName'] === 'MG') {
                          $vitamin_c = $value;
                          echo "The vitamin C content for $food is $value milligrams.<br>";
                      }
                      break;
                  case 'Calcium, Ca':
                      if ($nutrient['unitName'] === 'MG') {
                          $calcium = $value;
                          echo "The calcium content for $food is $value milligrams.<br>";
                      }
                      break;
                  case 'Iron, Fe':
                      if ($nutrient['unitName'] === 'MG') {
                          $iron = $value;
                          echo "The iron content for $food is $value milligrams.<br>";
                      }
                      break;
              }
          }
          // Prepare and bind
     $stmt = $con->prepare("INSERT INTO food_plan ( name, measurement, amount, total, calories, protein, fat, carbohydrates, fiber, sugars, cholesterol, sodium, vitamin_c, calcium, iron, potassium, magnesium, nitrogen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
     $stmt->bind_param("ssidssssssssssssss", $food, $measure, $amount,  $total, $calories, $protein, $fat, $carbohydrates, $fiber, $sugars, $cholesterol, $sodium, $vitamin_c, $calcium, $iron, $potassium, $magnesium, $nitrogen);

     // Execute the statement
     if ($stmt->execute()) {
         echo "<div class='alert alert-success'>Record inserted successfully</div>";
     } else {
         echo "<div class='alert alert-danger'>Error: " . $stmt->error;
   echo '</div>';
     }

     // Close connection
     $stmt->close();
 echo '</div>';
 } else {
     echo "<div class='alert alert-danger'>No information found for {$food}. Try again!</div>";
 }

}
?>



<h1>Enter food to calculate nutrition</h1>

<form action="" method="post">
   <div class="form-group form-group-lg">
     <label for="food">Busca o ingresa un alimento para ver su informacion nutricional:</label>
     <input type="text" id="food" name="food" class="form-control"  placeholder="red delicious apples / hard boiled eggs " required>
   </div>
   <div class="form-group form-group-lg">
     <label for="amount">Enter how many:</label>
     <input type="number" id="amount" name="amount" class="form-control"  required>
   </div>
   <div class="form-group form-group-lg">
   <label for="measure">Choose Measurement Scale:</label>
     <select id="measure" name="measure" class="form-control" required>
       <option value="">Choose</option>
       <option value="240">Cup(s) 240 G</option>
       <option value="120">1/2 Cup(s) 120 G</option>
       <option value="3899">Gallon(s) 3899 G</option>
       <option value="1000">Liter(s) 1000 G</option>
       <option value="38">Slice(s) 38 G</option>
       <option value="50">Piece(s) 50 G</option>
       <option value="5.69">Teaspoon(s) 5.69 G</option>
       <option value="14.175">Tablespoon(s) 14.175 G</option>
       <option value="28.35">Ounce(s) 28.35 G</option>
       <option value="113">Stick(s) 113 G</option>
       <option value="800">Loaf(s) 800 G</option>
       <option value="106">Can(s) 3.75oz 106 G</option>
       <option value="425.24">Can(s) 15oz 425.24 G</option>
       <option value="340.19">Can(s) 12oz 340.19 G</option>
     </select>
   </div>
       <button type="submit" name="submit" class="btn btn-success">Add Food</button>
   </form>

<?php
$sql = "SELECT * FROM food_plan";  // Consulta SQL para obtener todos los registros
$result = $con->query($sql);  // Ejecutar la consulta

if ($result->num_rows > 0) {
    // Mostrar los datos
    echo "<div class='form-group'>";
	echo "<table class='table'>
            <tr>
                <th>Name</th>
                <th>Amount</th>
                <th>Measurement</th>
                <th>Calories</th>
                <th>Protein</th>
                <th>Fat</th>
                <th>Carbohydrates</th>
                <th>Fiber</th>
                <th>Sugars</th>
                <th>Cholesterol</th>
                <th>Sodium</th>
                <th>Vitamin C</th>
                <th>Calcium</th>
                <th>Iron</th>
                <th>Potassium</th>
                <th>Magnesium</th>
            </tr>";
    
    // Iterar sobre los resultados
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['name']}</td>
                <td>{$row['amount']}</td>
                <td>{$row['measurement']}</td>
                <td>{$row['calories']}</td>
                <td>{$row['protein']}</td>
                <td>{$row['fat']}</td>
                <td>{$row['carbohydrates']}</td>
                <td>{$row['fiber']}</td>
                <td>{$row['sugars']}</td>
                <td>{$row['cholesterol']}</td>
                <td>{$row['sodium']}</td>
                <td>{$row['vitamin_c']}</td>
                <td>{$row['calcium']}</td>
                <td>{$row['iron']}</td>
                <td>{$row['potassium']}</td>
                <td>{$row['magnesium']}</td>
              </tr>";
    }
    
    echo "</table>";
	echo "</div>";
} else {
    echo "No records found";
}
?>



<?php
$plan_generated = false;

if (isset($_POST['submit_person'])) {
    $height = htmlspecialchars($_POST['height']);
    $weight = htmlspecialchars($_POST['weight']);
    $sex = htmlspecialchars($_POST['sex']);
    $goal = htmlspecialchars($_POST['goal']);
    $age = htmlspecialchars($_POST['age']);
    // $food = htmlspecialchars($_POST['food']);


    // Calculate BMR (Basal Metabolic Rate)
    $bmr = ($sex == 'male') 
        ? (88.36 + (13.4 * $weight) + (4.8 * $height) - (5.7 * $age)) 
        : (447.6 + (9.2 * $weight) + (3.1 * $height) - (4.3 * $age));

    switch ($goal) {
        case 'lose':
            $daily_calories = $bmr - 500;
            break;
        case 'gain':
            $daily_calories = $bmr + 500;
            break;
        case 'maintain':
        default:
            $daily_calories = $bmr;
            break;
    }

    echo "<h2>Recommended Daily Caloric Intake: $daily_calories kcal</h2>";

    // USDA API Details
    $api_url = 'https://api.nal.usda.gov/fdc/v1/foods/search';
    $api_key = apikey();
	
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $meals = ['Breakfast', 'Lunch', 'Dinner', 'Snack'];

    // Portion sizes (example values)
    $portion_sizes = [
        "cup" => 240,
        "oz" => 28,
        "g" => 100,
        "tbsp" => 15,
        "slice" => 50
    ];
	
    $food_options = [
        "Breakfast" => ["oatmeal", "eggs", "greek yogurt", "banana", "whole grain toast", "avocado"],
        "Lunch" => ["grilled chicken", "quinoa", "brown rice", "salmon", "chickpeas", "spinach salad"],
        "Dinner" => ["steak", "sweet potatoes", "grilled fish", "broccoli", "tofu stir-fry", "pasta"],
        "Snack" => ["almonds", "protein shake", "hummus", "carrot sticks", "cottage cheese", "apple"]
    ];

    echo "<h3>Your Weekly Meal Plan</h3>";
    // echo "<table border='1'><tr><th>Day</th><th>Meal</th><th>Food</th><th>Amount</th><th>Measure</th><th>Calories</th></tr>";

    foreach ($days as $day) {
		echo "<h3>$day</h3>";
        foreach ($meals as $meal) {
		    $amount = 1;
            $measure = array_rand($portion_sizes);
            $total_weight = $portion_sizes[$measure] * $amount;
			
            // Select a random food for the meal
            $food = $food_options[$meal][array_rand($food_options[$meal])];

            // Query the API for nutrition data
            $data = [
                'query' => $food,
                'pageSize' => 1,
                'api_key' => $api_key
            ];

            $ch = curl_init($api_url . '?' . http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);
	        $calories = $protein = $fat = $carbohydrates = $fiber = $sugars = null;
	            $cholesterol = $sodium = $vitamin_c = $calcium = $iron = null;
	            $vitamin_b12 = $potassium = $magnesium = $nitrogen = $alcohol = null;

            if (isset($result['foods'][0]['foodNutrients'])) {
                foreach ($result['foods'][0]['foodNutrients'] as $nutrient) {
                    $value = $nutrient['value'];
			        switch ($nutrient['nutrientName']) {
		                  case 'Potassium, K':
		                      if ($nutrient['unitName'] === 'MG') {
		                          $potassium = $value;
		                          // echo "The Potassium content for $food is $value milligrams.<br>";
		                      }
		                      break;
		                  case 'Magnesium':
		                      if ($nutrient['unitName'] === 'MG') {
		                          $magnesium = $value;
		                          // echo "The Magnesium content for $food is $value milligrams.<br>";
		                      }
		                      break;
		                  case 'Water':
		                      if ($nutrient['unitName'] === 'G') {
		                          $water = $value;
		                          // echo "The water content for $food is $value grams.<br>";
		                      }
		                      break;
		                  case 'Energy':
		                      if ($nutrient['unitName'] === 'KCAL') {
		                          $calories = $value;
		                          // echo "The calorie count for $food is $value calories.<br>";
		                      }
		                      break;
		                  case 'Protein':
		                      if ($nutrient['unitName'] === 'G') {
		                          $protein = $value;
		                          // echo "The protein content for $food is $value grams.<br>";
		                      }
		                      break;
		                  case 'Total lipid (fat)':
		                      if ($nutrient['unitName'] === 'G') {
		                          $fat = $value;
		                          // echo "The fat content for $food is $value grams.<br>";
		                      }
		                      break;
		                  case 'Carbohydrate, by difference':
		                      if ($nutrient['unitName'] === 'G') {
		                          $carbohydrates = $value;
		                          // echo "The carbohydrate content for $food is $value grams.<br>";
		                      }
		                      break;
		                  case 'Fiber, total dietary':
		                      if ($nutrient['unitName'] === 'G') {
		                          $fiber = $value;
		                          // echo "The fiber content for $food is $value grams.<br>";
		                      }
		                      break;
		                  case 'Sugars, total including NLEA':
		                      if ($nutrient['unitName'] === 'G') {
		                          $sugars = $value;
		                          // echo "The sugar content for $food is $value grams.<br>";
		                      }
		                      break;
		                  case 'Cholesterol':
		                      if ($nutrient['unitName'] === 'MG') {
		                          $cholesterol = $value;
		                          // echo "The cholesterol content for $food is $value milligrams.<br>";
		                      }
		                      break;
		                  case 'Sodium, Na':
		                      if ($nutrient['unitName'] === 'MG') {
		                          $sodium = $value;
		                          // echo "The sodium content for $food is $value milligrams.<br>";
		                      }
		                      break;
		                  case 'Vitamin C, total ascorbic acid':
		                      if ($nutrient['unitName'] === 'MG') {
		                          $vitamin_c = $value;
		                          // echo "The vitamin C content for $food is $value milligrams.<br>";
		                      }
		                      break;
		                  case 'Calcium, Ca':
		                      if ($nutrient['unitName'] === 'MG') {
		                          $calcium = $value;
		                          // echo "The calcium content for $food is $value milligrams.<br>";
		                      }
		                      break;
		                  case 'Iron, Fe':
		                      if ($nutrient['unitName'] === 'MG') {
		                          $iron = $value;
		                          // echo "The iron content for $food is $value milligrams.<br>";
		                      }
		                      break;
			              }
			          }
                }

	            // Output meal data
				echo "
				    <div class='meal'>
				        <h4 class='meal-title'>$meal:</h4>
				        <p class='meal-details'>
				            <span class='food-name'>$food:</span>
				            <span class='amount'>porcion: $total_weight g</span> 
				            <span class='calories'>{$calories} kcal</span>
							<span class='calories'>colesterol {$cholesterol} mg</span>
							<span class='calories'>carbohidratos {$carbohydrates} g</span>
				        </p>
				    </div>";

					// 				            <span class='measure'>$measure</span>, 
                // Display meal plan
                // echo "<tr>
//                         <td>$day</td>
//                         <td>$meal</td>
//                         <td>$food</td>
//                         <td>$amount</td>
//                         <td>$measure</td>
//                         <td>{$calories} kcal</td>
//                       </tr>";
            }
        }
    

    // echo "</table>";
    $plan_generated = true;
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
        <label for="age">Age:</label>
        <input type="number" id="age" name="age" class="form-control" required>
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
    <button type="submit" name="submit_person" class="btn btn-primary">Generate Meal Plan</button>
</form>

<?php require_once('footer.php'); ?>
