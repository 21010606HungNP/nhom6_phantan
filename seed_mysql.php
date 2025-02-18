<?php
require_once('includes/config.php');
require_once('vendor/autoload.php');

$faker = Faker\Factory::create();

// 📌 Lấy danh sách brand từ database
$sql = "SELECT id, BrandName FROM tblbrands";
$query = $dbh->prepare($sql);
$query->execute();
$brands = $query->fetchAll(PDO::FETCH_ASSOC);

// 📌 Lấy danh sách địa điểm từ bảng locations
$sqlLocations = "SELECT id, city_name FROM locations";
$queryLocations = $dbh->prepare($sqlLocations);
$queryLocations->execute();
$locations = $queryLocations->fetchAll(PDO::FETCH_ASSOC);

// 📌 Lấy danh sách ảnh trong thư mục
$imageDir = __DIR__ . "/admin/img/vehicleimages/";
$images = array_values(array_diff(scandir($imageDir), array('.', '..')));

if (empty($images)) {
    die("❌ Không tìm thấy ảnh trong thư mục vehicleimages/");
}

// Tăng thời gian thực thi và bộ nhớ PHP (nếu cần thiết)
ini_set('max_execution_time', 300); // 5 phút
ini_set('memory_limit', '512M');   // 512MB

// Batch insert
$batchSize = 200; // Số lượng bản ghi trong mỗi batch (giảm xuống để an toàn)
$vehicles = [];   // Mảng lưu trữ dữ liệu tạm thời
$totalInserted = 0; // Đếm tổng số bản ghi đã chèn

try {
    for ($i = 1; $i <= 50000; $i++) {
        $brand = $brands[array_rand($brands)];
        $fuel = $faker->randomElement(['Petrol', 'Diesel', 'CNG', 'Electric']);
        $seats = $faker->randomElement([2, 4, 5, 7, 9]);
        $year = $faker->numberBetween(2000, 2024);
        $location = $locations[array_rand($locations)];
        $image = "/admin/img/vehicleimages/" . $faker->randomElement($images);
        $price = $faker->numberBetween(20, 200);
        $title = $brand['BrandName'] . " " . $faker->word;

        // Thêm bản ghi vào mảng batch
        $vehicles[] = "('$title', {$brand['id']}, '$fuel', $price, $year, {$location['id']}, $seats, '$image')";

        // Khi đạt batchSize, thực hiện insert
        if (count($vehicles) >= $batchSize) {
            $values = implode(", ", $vehicles);
            $sql = "INSERT INTO tblvehicles 
                    (VehiclesTitle, VehiclesBrand, FuelType, PricePerDay, ModelYear, location_id, SeatingCapacity, Vimage1) 
                    VALUES $values";
            $dbh->exec($sql); // Thực hiện batch insert
            $totalInserted += count($vehicles); // Tăng tổng số bản ghi đã chèn
            $vehicles = []; // Reset mảng để chuẩn bị batch tiếp theo
        }
    }

    // Chèn các bản ghi còn lại nếu có
    if (!empty($vehicles)) {
        $values = implode(", ", $vehicles);
        $sql = "INSERT INTO tblvehicles 
                (VehiclesTitle, VehiclesBrand, FuelType, PricePerDay, ModelYear, location_id, SeatingCapacity, Vimage1) 
                VALUES $values";
        $dbh->exec($sql);
        $totalInserted += count($vehicles);
        echo "Inserted remaining records. Total inserted: $totalInserted\n";
    }

    echo "✅ Seeded $totalInserted vehicles into MySQL!";
} catch (PDOException $e) {
    echo "❌ Error during batch insert: " . $e->getMessage() . "\n";
}
