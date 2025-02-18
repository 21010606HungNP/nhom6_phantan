    <?php
    require 'vendor/autoload.php';
    require 'includes/config.php'; // Kết nối MySQL (PDO)

    use Elastic\Elasticsearch\ClientBuilder;

    // Kết nối Elasticsearch
    $client = ClientBuilder::create()->setHosts(['http://localhost:9200'])->build();

    // ✅ CẬP NHẬT QUERY: Lấy thêm `BrandName` từ `tblbrands`
    $query = "SELECT v.id, v.VehiclesTitle, v.VehiclesBrand, b.BrandName, v.VehiclesOverview, 
                    v.PricePerDay, v.FuelType, v.ModelYear, v.SeatingCapacity, v.Vimage1, 
                    v.AirConditioner, v.PowerSteering, v.DriverAirbag, v.PassengerAirbag, v.LeatherSeats 
            FROM tblvehicles v
            JOIN tblbrands b ON v.VehiclesBrand = b.id"; // 🔥 JOIN với `tblbrands`

    $stmt = $dbh->prepare($query);
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Kiểm tra nếu không có dữ liệu
    if (count($vehicles) === 0) {
        die("Không có dữ liệu trong bảng tblvehicles.\n");
    }

    $bulkParams = ['body' => []];

    foreach ($vehicles as $row) {
        $bulkParams['body'][] = [
            'index' => [
                '_index' => 'vehicles',
                '_id'    => $row['id']
            ]
        ];
        $bulkParams['body'][] = [
            'title'       => $row['VehiclesTitle'],
            'brand_id'    => $row['VehiclesBrand'],  // 🚀 Vẫn giữ `brand_id`
            'brand_name'  => $row['BrandName'],      // 🔥 Thêm `brand_name`
            'overview'    => $row['VehiclesOverview'],
            'price'       => $row['PricePerDay'],
            'fuel'        => $row['FuelType'],
            'year'        => $row['ModelYear'],
            'seats'       => $row['SeatingCapacity'],
            'image'       => $row['Vimage1'],
            'features'    => [
                'air_conditioner'    => (bool) $row['AirConditioner'],
                'power_steering'     => (bool) $row['PowerSteering'],
                'driver_airbag'      => (bool) $row['DriverAirbag'],
                'passenger_airbag'   => (bool) $row['PassengerAirbag'],
                'leather_seats'      => (bool) $row['LeatherSeats']
            ]
        ];
    }

    // Gửi dữ liệu lên Elasticsearch (Bulk Indexing)
    try {
        $response = $client->bulk($bulkParams);
        echo "Đã index " . count($vehicles) . " xe vào Elasticsearch!\n";
    } catch (Exception $e) {
        echo "Lỗi khi index vào Elasticsearch: " . $e->getMessage() . "\n";
    }
