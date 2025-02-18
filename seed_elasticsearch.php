<?php
require __DIR__ . '/vendor/autoload.php';
include('includes/config.php');

use Elastic\Elasticsearch\ClientBuilder;
use Faker\Factory as Faker;

// Kết nối Elasticsearch
$client = ClientBuilder::create()->setHosts(['http://localhost:9200'])->build();
$dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 📌 1️⃣ Lấy danh sách brand từ database
$sql = "SELECT id, BrandName FROM tblbrands";
$query = $dbh->prepare($sql);
$query->execute();
$brands = $query->fetchAll(PDO::FETCH_ASSOC);

// 📌 2️⃣ Lấy danh sách địa điểm từ bảng locations
$sqlLocations = "SELECT id, city_name FROM locations";
$queryLocations = $dbh->prepare($sqlLocations);
$queryLocations->execute();
$locations = $queryLocations->fetchAll(PDO::FETCH_ASSOC);

// 📌 3️⃣ Lấy danh sách ảnh trong thư mục
$imageDir = __DIR__ . "/admin/img/vehicleimages/";
$images = array_values(array_diff(scandir($imageDir), array('.', '..')));

if (empty($images)) {
    die("Không tìm thấy ảnh trong thư mục vehicleimages/");
}

// 📌 4️⃣ Tạo dữ liệu giả lập và đẩy vào Elasticsearch
$faker = Faker::create();
$vehicles = [];
$batchSize = 500; // Đẩy từng nhóm 500 xe để tối ưu hiệu suất

$startId = 50001; // Bắt đầu _id từ 50001 (tiếp nối dữ liệu cũ)
$totalToInsert = 450000; // Số lượng dữ liệu muốn thêm
$endId = $startId + $totalToInsert - 1; // Tính _id cuối cùng

for ($i = $startId; $i <= $endId; $i++) {
    $brand = $brands[array_rand($brands)];
    $fuelTypes = ['Petrol', 'Diesel', 'CNG', 'Electric'];
    $seats = [2, 4, 5, 7, 9];
    $years = range(2000, 2024);
    $location = $locations[array_rand($locations)];

    $vehicle = [
        'brand_id' => $brand['id'],
        'brand_name' => $brand['BrandName'],
        'title' => $brand['BrandName'] . " " . $faker->word . " " . $faker->year,
        'year' => $faker->randomElement($years),
        'fuel' => $faker->randomElement($fuelTypes),
        'seats' => $faker->randomElement($seats),
        'price' => $faker->numberBetween(20, 200),
        'location_id' => $location['id'],
        'city_name' => $location['city_name'],
        'image' => "/admin/img/vehicleimages/" . $faker->randomElement($images),
    ];

    $vehicles[] = ['index' => ['_index' => 'vehicles', '_id' => $i]]; // Dùng _id = $i
    $vehicles[] = $vehicle;

    // Mỗi 500 xe thì gửi 1 lần để tối ưu tốc độ
    if (count($vehicles) >= $batchSize * 2) {
        $params = ['body' => $vehicles];
        $client->bulk($params);
        $vehicles = []; // Xóa mảng để tiếp tục batch tiếp theo
    }
}

// Gửi các xe còn lại
if (!empty($vehicles)) {
    $params = ['body' => $vehicles];
    $client->bulk($params);
}

echo "✅ Đã thêm 450,000 xe vào Elasticsearch!";
