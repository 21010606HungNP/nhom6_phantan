<?php
require __DIR__ . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;

$client = ClientBuilder::create()->setHosts(['http://localhost:9200'])->build();

$title = $_GET['title'] ?? null; // Nhận title từ request
$brand = $_GET['brand'] ?? null;
$fuelType = $_GET['fuelType'] ?? null;
$minPrice = isset($_GET['minPrice']) ? (int) $_GET['minPrice'] : null;
$maxPrice = isset($_GET['maxPrice']) ? (int) $_GET['maxPrice'] : null;
$modelYear = $_GET['modelYear'] ?? null;
$location = $_GET['location'] ?? null; // Nhận location từ request
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Cấu hình params Elasticsearch
$params = [
    'index' => 'vehicles',
    'body'  => [
        'from' => $offset,
        'size' => $perPage,
        'query' => [
            'bool' => [
                'must' => [],  // Các điều kiện 'phải có'
                'filter' => [] // Các bộ lọc chính xác
            ]
        ],
        'sort' => [
            '_score' => 'desc', // Sắp xếp theo độ phù hợp
            'price' => 'asc'    // Sau đó sắp xếp giá tăng dần
        ]
    ]
];

// Kết hợp Autocomplete và Fuzzy Search cho title
if (!empty($title)) {
    $params['body']['query']['bool']['should'][] = [
        'multi_match' => [
            'query' => $title,
            'fields' => ['title.autocomplete^2'], // Ưu tiên autocomplete
            'type' => 'bool_prefix'
        ]
    ];
    $params['body']['query']['bool']['should'][] = [
        'multi_match' => [
            'query' => $title,
            'fields' => ['title.fuzzy'], // Sử dụng fuzzy subfield
            'fuzziness' => 'AUTO',       // Hoặc đặt cụ thể 'fuzziness' => 1
            'prefix_length' => 1         // Tối thiểu 1 ký tự đầu tiên phải chính xác
        ]
    ];
}

// Bộ lọc theo brand
if (!empty($brand)) {
    $params['body']['query']['bool']['filter'][] = [
        'term' => ['brand_name.keyword' => $brand]
    ];
}

// Bộ lọc theo fuelType
if (!empty($fuelType)) {
    $params['body']['query']['bool']['filter'][] = [
        'term' => ['fuel' => $fuelType]
    ];
}

// Bộ lọc theo khoảng giá
if (!is_null($minPrice) && !is_null($maxPrice)) {
    $params['body']['query']['bool']['filter'][] = [
        'range' => [
            'price' => [
                'gte' => $minPrice,
                'lte' => $maxPrice
            ]
        ]
    ];
}

// Bộ lọc theo năm sản xuất
if (!empty($modelYear)) {
    $params['body']['query']['bool']['filter'][] = [
        'term' => ['year' => (int) $modelYear]
    ];
}

// Bộ lọc theo location
if (!empty($location)) {
    $params['body']['query']['bool']['filter'][] = [
        'term' => ['location_id' => (int) $location]
    ];
}

// 🛠 Chạy truy vấn tìm kiếm
try {
    $response = $client->search($params);
    $totalResults = $response['hits']['total']['value'] ?? 0;

    // Nếu không có kết quả, trả về thông báo
    if ($totalResults === 0) {
        echo json_encode([
            'total' => 0,
            'message' => 'No cars match your search criteria.',
            'results' => []
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'total' => $totalResults,
            'results' => $response['hits']['hits']
        ], JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
