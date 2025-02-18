<?php
require_once('vendor/autoload.php'); // ElasticSearch library
include('includes/config.php'); // MySQL configuration

use Elastic\Elasticsearch\ClientBuilder;

// Kết nối MySQL
$dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Kết nối Elasticsearch
$client = ClientBuilder::create()->setHosts(['http://localhost:9200'])->build();

// Hàm đo thời gian thực thi
function measureExecutionTime($callback)
{
    $start = microtime(true);
    $callback();
    $end = microtime(true);
    return $end - $start;
}

// 🛠 Benchmark MySQL với nhiều điều kiện
function benchmarkMySQLComplex($dbh, $keyword, $minPrice, $maxPrice, $year, $locationId)
{
    $query = "SELECT * FROM tblvehicles 
              WHERE VehiclesTitle LIKE :keyword 
              AND PricePerDay BETWEEN :minPrice AND :maxPrice
              AND ModelYear = :year
              AND location_id = :locationId";
    $stmt = $dbh->prepare($query);
    $stmt->bindValue(':keyword', '%' . $keyword . '%', PDO::PARAM_STR);
    $stmt->bindValue(':minPrice', $minPrice, PDO::PARAM_INT);
    $stmt->bindValue(':maxPrice', $maxPrice, PDO::PARAM_INT);
    $stmt->bindValue(':year', $year, PDO::PARAM_INT);
    $stmt->bindValue(':locationId', $locationId, PDO::PARAM_INT);

    $executionTime = measureExecutionTime(function () use ($stmt) {
        $stmt->execute();
        $stmt->fetchAll(PDO::FETCH_ASSOC);
    });

    echo "MySQL Complex Query Execution Time: " . $executionTime . " seconds\n";
    return $executionTime;
}

// 🛠 Benchmark ElasticSearch với nhiều điều kiện
function benchmarkElasticsearchComplex($client, $keyword, $minPrice, $maxPrice, $year, $locationId)
{
    $params = [
        'index' => 'vehicles',
        'body' => [
            'query' => [
                'bool' => [
                    'must' => [
                        'match' => ['title' => $keyword]
                    ],
                    'filter' => [
                        ['range' => ['price' => ['gte' => $minPrice, 'lte' => $maxPrice]]],
                        ['term' => ['year' => $year]],
                        ['term' => ['location_id' => $locationId]]
                    ]
                ]
            ]
        ]
    ];

    $executionTime = measureExecutionTime(function () use ($client, $params) {
        $client->search($params);
    });

    echo "ElasticSearch Complex Query Execution Time: " . $executionTime . " seconds\n";
    return $executionTime;
}

// 🛠 Benchmark ElasticSearch Fuzzy Search
function benchmarkElasticsearchFuzzy($client, $keyword)
{
    $params = [
        'index' => 'vehicles',
        'body' => [
            'query' => [
                'fuzzy' => [
                    'title' => [
                        'value' => $keyword,
                        'fuzziness' => 'AUTO'
                    ]
                ]
            ]
        ]
    ];

    $executionTime = measureExecutionTime(function () use ($client, $params) {
        $client->search($params);
    });

    echo "ElasticSearch Fuzzy Query Execution Time: " . $executionTime . " seconds\n";
    return $executionTime;
}

// 🏁 Chạy benchmark
$keyword = "Toyota";
$keyword2 = "Toyato";        // Từ khóa tìm kiếm
$minPrice = 50;            // Giá tối thiểu
$maxPrice = 150;           // Giá tối đa
$year = 2015;              // Năm sản xuất
$locationId = 1;           // ID địa điểm (ví dụ: Hà Nội)

echo "Running complex benchmark with keyword: $keyword\n";

echo "\n--- MySQL Complex Query ---\n";
$mysqlComplexTime = benchmarkMySQLComplex($dbh, $keyword, $minPrice, $maxPrice, $year, $locationId);

echo "\n--- ElasticSearch Complex Query ---\n";
$elasticComplexTime = benchmarkElasticsearchComplex($client, $keyword, $minPrice, $maxPrice, $year, $locationId);

echo "\n--- ElasticSearch Fuzzy Search ---\n";
$elasticFuzzyTime = benchmarkElasticsearchFuzzy($client, $keyword2);

echo "\n--- Comparison ---\n";
echo "MySQL Complex Query Time: $mysqlComplexTime seconds\n";
echo "ElasticSearch Complex Query Time: $elasticComplexTime seconds\n";
echo "ElasticSearch Fuzzy Query Time: $elasticFuzzyTime seconds\n";
