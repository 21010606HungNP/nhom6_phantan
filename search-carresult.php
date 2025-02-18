<?php
session_start();
include('includes/config.php');
error_reporting(0);

// Nhận dữ liệu tìm kiếm từ AJAX hoặc form
$brand = isset($_POST['brand']) ? $_POST['brand'] : "";
$fueltype = isset($_POST['fueltype']) ? $_POST['fueltype'] : "";
$price_range = isset($_POST['price_range']) ? $_POST['price_range'] : "";
$model_year = isset($_POST['model_year']) ? $_POST['model_year'] : "";

// Xử lý phân trang
$page = isset($_POST['page']) ? $_POST['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Tạo truy vấn SQL linh hoạt
$sql = "SELECT tblvehicles.*, tblbrands.BrandName FROM tblvehicles 
        JOIN tblbrands ON tblbrands.id = tblvehicles.VehiclesBrand 
        WHERE 1=1";

// Thêm điều kiện tìm kiếm nếu có
if (!empty($brand)) {
  $sql .= " AND tblvehicles.VehiclesBrand = :brand";
}
if (!empty($fueltype)) {
  $sql .= " AND tblvehicles.FuelType = :fueltype";
}
if (!empty($price_range)) {
  list($minPrice, $maxPrice) = explode("-", $price_range);
  $sql .= " AND tblvehicles.PricePerDay BETWEEN :minPrice AND :maxPrice";
}
if (!empty($model_year)) {
  $sql .= " AND tblvehicles.ModelYear = :model_year";
}

// Thêm phân trang
$sql .= " LIMIT :limit OFFSET :offset";

$query = $dbh->prepare($sql);

// Gán giá trị nếu có
if (!empty($brand)) {
  $query->bindParam(':brand', $brand, PDO::PARAM_STR);
}
if (!empty($fueltype)) {
  $query->bindParam(':fueltype', $fueltype, PDO::PARAM_STR);
}
if (!empty($price_range)) {
  $query->bindParam(':minPrice', $minPrice, PDO::PARAM_INT);
  $query->bindParam(':maxPrice', $maxPrice, PDO::PARAM_INT);
}
if (!empty($model_year)) {
  $query->bindParam(':model_year', $model_year, PDO::PARAM_INT);
}
$query->bindParam(':limit', $limit, PDO::PARAM_INT);
$query->bindParam(':offset', $offset, PDO::PARAM_INT);
$query->execute();

$results = $query->fetchAll(PDO::FETCH_OBJ);

// Hiển thị kết quả
if ($query->rowCount() > 0) {
  foreach ($results as $result) { ?>
    <div class="product-listing-m gray-bg">
      <div class="product-listing-img">
        <img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1); ?>" class="img-responsive" />
      </div>
      <div class="product-listing-content">
        <h5>
          <a href="vehical-details.php?vhid=<?php echo htmlentities($result->id); ?>">
            <?php echo htmlentities($result->BrandName); ?> , <?php echo htmlentities($result->VehiclesTitle); ?>
          </a>
        </h5>
        <p class="list-price">$<?php echo htmlentities($result->PricePerDay); ?> Per Day</p>
        <ul>
          <li><i class="fa fa-user"></i> <?php echo htmlentities($result->SeatingCapacity); ?> seats</li>
          <li><i class="fa fa-calendar"></i> <?php echo htmlentities($result->ModelYear); ?> model</li>
          <li><i class="fa fa-car"></i> <?php echo htmlentities($result->FuelType); ?></li>
        </ul>
        <a href="vehical-details.php?vhid=<?php echo htmlentities($result->id); ?>" class="btn">View Details</a>
      </div>
    </div>
<?php }
} else {
  echo "<p>No results found.</p>";
}
?>