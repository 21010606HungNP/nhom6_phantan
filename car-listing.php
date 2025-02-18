<?php
session_start();
include('includes/config.php');
error_reporting(0);
?>

<!DOCTYPE HTML>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Car Rental Portal | Car Listing</title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
  <link rel="stylesheet" href="assets/css/style.css" type="text/css">
  <link rel="stylesheet" href="assets/css/font-awesome.min.css" type="text/css">

  <!-- JQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>

  <style>
    /* CSS cho autocomplete */
    .autocomplete-suggestions {
      list-style: none;
      padding: 0;
      margin: 0;
      position: absolute;
      background: white;
      border: 1px solid #ddd;
      width: 100%;
      max-height: 200px;
      overflow-y: auto;
      z-index: 1000;
    }

    .autocomplete-suggestions li {
      padding: 8px 10px;
      cursor: pointer;
    }

    .autocomplete-suggestions li:hover {
      background-color: #f0f0f0;
    }
  </style>

</head>

<body>

  <!--Header-->
  <?php include('includes/header.php'); ?>
  <!-- /Header -->

  <!--Page Header-->
  <section class="page-header listing_page">
    <div class="container">
      <div class="page-header_wrap">
        <div class="page-heading">
          <h1>Car Listing</h1>
        </div>
        <ul class="coustom-breadcrumb">
          <li><a href="#">Home</a></li>
          <li>Car Listing</li>
        </ul>
      </div>
    </div>
    <div class="dark-overlay"></div>
  </section>
  <!-- /Page Header-->

  <!--Listing Section-->
  <section class="listing-page">
    <div class="container">
      <div class="row">
        <!--Side-Bar: Bộ lọc tìm kiếm -->
        <aside class="col-md-3">
          <div class="sidebar_widget">
            <div class="widget_heading">
              <h5><i class="fa fa-filter"></i> Find Your Car </h5>
            </div>
            <div class="sidebar_filter">
              <form id="searchForm">
                <div class="form-group">
                  <input type="text" class="form-control filter-input" name="title" id="searchTitle" placeholder="Enter car title">
                  <ul class="autocomplete-suggestions" id="autocompleteSuggestions"></ul>
                </div>
                <div class="form-group select">
                  <select class="form-control filter-input" name="brand">
                    <option value="">Select Brand</option>
                    <?php
                    $sql = "SELECT * FROM tblbrands";
                    $query = $dbh->prepare($sql);
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                    foreach ($results as $result) {
                      echo '<option value="' . $result->BrandName . '">' . $result->BrandName . '</option>';
                    }
                    ?>
                  </select>
                </div>
                <div class="form-group select">
                  <select class="form-control filter-input" name="fuelType">
                    <option value="">Select Fuel Type</option>
                    <option value="Petrol">Petrol</option>
                    <option value="Diesel">Diesel</option>
                    <option value="CNG">CNG</option>
                    <option value="Electric">Electric</option>
                  </select>
                </div>
                <div class="form-group select">
                  <select class="form-control filter-input" name="price_range">
                    <option value="">Select Price Range</option>
                    <option value="0-50">$0 - $50</option>
                    <option value="50-100">$50 - $100</option>
                    <option value="100-200">$100 - $200</option>
                  </select>
                </div>
                <div class="form-group select">
                  <select class="form-control filter-input" name="modelYear">
                    <option value="">Select Model Year</option>
                    <?php for ($year = date("Y"); $year >= 2000; $year--) { ?>
                      <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-group select">
                  <select class="form-control filter-input" name="location">
                    <option value="">Select Location</option>
                    <?php
                    $sql = "SELECT * FROM locations";
                    $query = $dbh->prepare($sql);
                    $query->execute();
                    $results = $query->fetchAll(PDO::FETCH_OBJ);
                    foreach ($results as $result) {
                      echo '<option value="' . $result->id . '">' . $result->city_name . '</option>';
                    }
                    ?>
                  </select>
                </div>
              </form>
            </div>
          </div>
        </aside>
        <!--/Side-Bar-->

        <!-- Kết quả tìm kiếm -->
        <div class="col-md-9">
          <div id="searchResults"></div>
          <div id="pagination"></div>
        </div>
      </div>
    </div>
  </section>
  <!-- /Listing -->

  <!--Footer -->
  <?php include('includes/footer.php'); ?>

  <!-- AJAX Tìm kiếm -->
  <script>
    $(document).ready(function() {
      let currentPage = 1;
      const perPage = 10;

      function fetchResults(page = 1) {
        let formData = {
          title: $("input[name='title']").val(),
          brand: $("select[name='brand']").val(),
          fuelType: $("select[name='fuelType']").val(),
          modelYear: $("select[name='modelYear']").val(),
          location: $("select[name='location']").val(),
          page: page
        };

        let priceRange = $("select[name='price_range']").val();
        if (priceRange) {
          let [min, max] = priceRange.split("-");
          formData.minPrice = min;
          formData.maxPrice = max;
        }

        $.ajax({
          url: "search_vehicles.php",
          type: "GET",
          data: formData,
          dataType: "json",
          success: function(response) {
            let html = "";
            if (response.results.length > 0) {
              response.results.forEach(car => {
                let vehicle = car._source || {};
                let vehicleId = car._id;

                let imagePath = vehicle.image.startsWith("http") ?
                  vehicle.image :
                  "http://localhost/carrental" + vehicle.image;
                imagePath = encodeURI(imagePath);

                html += `
                                <div class="listing-item gray-bg">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="vehicle-img">
                                                <img src="${imagePath}" class="img-responsive" alt="${vehicle.title}">
                                            </div>                                                                                                 
                                        </div>
                                        <div class="col-md-7">
                                            <div class="vehicle-content">
                                                <h5><a href="vehical-details.php?vhid=${vehicleId}" style="color: black;">${vehicle.title}</a></h5>
                                                <p class="price" style="font-weight: bold;">$${vehicle.price} Per Day</p>
                                                <ul class="features_list horizontal-list">
                                                    <li><i class="fa fa-user"></i> ${vehicle.seats} seats</li>
                                                    <li><i class="fa fa-calendar"></i> ${vehicle.year} model</li>
                                                    <li><i class="fa fa-car"></i> ${vehicle.fuel}</li>
                                                    <li><i class="fa fa-map-marker"></i> ${vehicle.city_name}</li>
                                                </ul>
                                                <a href="vehical-details.php?vhid=${vehicleId}" class="btn btn-danger btn-sm">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
              });
              generatePagination(response.total, page);
            } else {
              html = "<p class='text-center'>No cars found.</p>";
            }
            $("#searchResults").html(html);
          }
        });
      }

      function generatePagination(total, currentPage) {
        let totalPages = Math.ceil(total / perPage);
        let paginationHTML = `<nav><ul class="pagination justify-content-center">`;

        if (currentPage > 1) {
          paginationHTML += `<li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo; Previous</a>
                </li>`;
        }

        paginationHTML += `<li class="page-item ${currentPage === 1 ? 'active' : ''}">
                <a class="page-link" href="#" data-page="1">1</a>
            </li>`;

        if (currentPage > 4) {
          paginationHTML += `<li class="page-item">
                    <a class="page-link pagination-input-trigger" href="#">...</a>
                    <input type="number" class="pagination-input hidden" min="1" max="${totalPages}" placeholder="Go">
                </li>`;
        }

        let startPage = Math.max(2, currentPage - 2);
        let endPage = Math.min(totalPages - 1, currentPage + 2);

        for (let i = startPage; i <= endPage; i++) {
          paginationHTML += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
        }

        if (currentPage < totalPages - 3) {
          paginationHTML += `<li class="page-item">
                    <a class="page-link pagination-input-trigger" href="#">...</a>
                    <input type="number" class="pagination-input hidden" min="1" max="${totalPages}" placeholder="Go">
                </li>`;
        }

        paginationHTML += `<li class="page-item ${currentPage === totalPages ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
            </li>`;

        if (currentPage < totalPages) {
          paginationHTML += `<li class="page-item">
                    <a class="page-link" href="#" data-page="${currentPage + 1}">Next &raquo;</a>
                </li>`;
        }

        paginationHTML += `</ul></nav>`;
        $("#pagination").html(paginationHTML);

        $(".pagination-input-trigger").on("click", function(e) {
          e.preventDefault();
          $(this).siblings(".pagination-input").removeClass("hidden").focus();
        });

        $(".pagination-input").on("keypress", function(e) {
          if (e.which === 13) {
            let page = parseInt($(this).val());
            if (page >= 1 && page <= totalPages) {
              changePage(page);
            }
          }
        });
      }

      function autocompleteTitle(query) {
        $.ajax({
          url: "search_vehicles.php",
          type: "GET",
          data: {
            title: query,
            page: 1,
            perPage: 5
          },
          dataType: "json",
          success: function(response) {
            let suggestions = "";
            if (response.results.length > 0) {
              response.results.forEach(car => {
                let vehicle = car._source || {};
                suggestions += `<li data-value="${vehicle.title}">${vehicle.title}</li>`;
              });
            } else {
              suggestions = "<li>No suggestions found.</li>";
            }
            $("#autocompleteSuggestions").html(suggestions).show();
          }
        });
      }

      $(document).on("input", "#searchTitle", function() {
        let query = $(this).val();
        if (query.length >= 2) {
          autocompleteTitle(query);
        } else {
          $("#autocompleteSuggestions").hide();
        }
      });

      $(document).on("keypress", "#searchTitle", function(e) {
        if (e.which === 13) { // 13 là mã phím Enter
          e.preventDefault();
          fetchResults(1); // Kích hoạt tìm kiếm với tiêu đề hiện tại
          $("#autocompleteSuggestions").hide(); // Ẩn gợi ý autocomplete
        }
      });

      $(document).on("click", "#autocompleteSuggestions li", function() {
        let value = $(this).data("value");
        if (value) {
          $("#searchTitle").val(value);
          $("#autocompleteSuggestions").hide();
          fetchResults(1);
        }
      });

      $(document).on("click", ".page-link", function(e) {
        e.preventDefault();
        let page = $(this).data("page");

        if (page) {
          fetchResults(page);

          // Thêm easing để cuộn chậm dần
          $("html, body").animate({
              scrollTop: $(".listing-page").offset().top
            },
            1000, // Thời gian cuộn (800ms)
            "easeOutExpo" // Easing: chậm dần khi gần tới đích
          );
        }
      });

      $(".filter-input").on("change", function() {
        fetchResults(1);
      });

      fetchResults();
    });
  </script>


</body>

</html>