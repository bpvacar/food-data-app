<?php

function apikey(){
  $apikey='LHh0crwxDxtunVmXVeiW7m3HUDkgmK70W4chycPp';
  return $apikey;
}
// Function to generate a meal based on daily caloric intake
function generateMeal($daily_calories, $food_options, $meal_type) {
    // Calculate the calories per meal (assuming 3 meals and 1 snack per day)
    $meal_calories = $daily_calories / 4; // Simple split, can be adjusted per meal

    $meal_items = [];
    $total_calories = 0;
    
    // Select random foods for the given meal type (e.g., Breakfast, Lunch, Dinner, Snack)
    $selected_foods = $food_options[$meal_type];

    // Loop to randomly pick food items for the meal
    while ($total_calories < $meal_calories || count($selected_foods) < 5) {
        // Randomly select a food from the list
        $food_item = $selected_foods[array_rand($selected_foods)];
        
        // Call the USDA API to get nutritional information for the food item
        $food_data = fetchFoodData($food_item);
        
        if ($food_data) {
            $food_name = $food_data['description'];
            $calories_per_unit = $food_data['calories'];  // Assume this is in kcal per 100g
            $portion_size = getPortionSize($food_name);

            // Calculate the amount of food to match the meal calorie target
            $portion_calories = ($calories_per_unit / 100) * $portion_size;

            // Check if adding this food exceeds the daily calories for the meal
            if ($total_calories + $portion_calories <= $meal_calories) {
                $meal_items[] = [
                    'food' => $food_name,
                    'amount' => $portion_size,
                    'measure' => 'g',  // Can be adjusted as necessary
                    'calories' => round($portion_calories, 2)
                ];
                $total_calories += $portion_calories;
            }
        }
    }

    return $meal_items;
}

// Function to fetch food data from the USDA API
function fetchFoodData($food_item) {
    $api_url = 'https://api.nal.usda.gov/fdc/v1/foods/search';
    $data = [
        'query' => $food_item,
        'pageSize' => 1,
        'api_key' => apikey()
    ];
    $ch = curl_init($api_url . '?' . http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);

    $food_data = [];
    if (isset($result['foods'][0]['foodNutrients'])) {
        foreach ($result['foods'][0]['foodNutrients'] as $nutrient) {
            if ($nutrient['nutrientName'] == 'Energy' && $nutrient['unitName'] == 'KCAL') {
                $food_data['calories'] = $nutrient['value'];
                break;
            }
        }
        $food_data['description'] = $result['foods'][0]['description'];
    }

    return $food_data;
}

// Function to determine portion size based on food type (example, could be more advanced)
function getPortionSize($food_name) {
    // Example logic to assign portion sizes based on food type
    $portion_sizes = [
        "oatmeal" => 40,
        "eggs" => 50,
        "greek yogurt" => 150,
        "banana" => 120,
        "whole grain toast" => 30,
        "avocado" => 100,
        "grilled chicken" => 150,
        "quinoa" => 50,
        "brown rice" => 50,
        "salmon" => 120,
        "chickpeas" => 100,
        "spinach salad" => 100,
        "steak" => 200,
        "sweet potatoes" => 150,
        "grilled fish" => 120,
        "broccoli" => 100,
        "tofu stir-fry" => 150,
        "pasta" => 100,
        "almonds" => 30,
        "protein shake" => 200,
        "hummus" => 30,
        "carrot sticks" => 80,
        "cottage cheese" => 100,
        "apple" => 150
    ];

    // Default portion size if food is not recognized
    return $portion_sizes[$food_name] ?? 100;  // Return 100g if not in the list
}
?>
