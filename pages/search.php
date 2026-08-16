<?php
// Search page - normal form GET (no AJAX)
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in() && is_owner()) {
    redirect(BASE_URL . 'owner/dashboard.php');
}

$location = trim($_GET['location'] ?? '');
$guests = max(1, (int)($_GET['guests'] ?? 1));
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 0);
$selectedAmenities = $_GET['amenity'] ?? [];
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;

$where = 'h.is_active = 1';
$params = [];

if ($location !== '') {
    $where .= ' AND (h.city LIKE ? OR h.state LIKE ? OR h.title LIKE ?)';
    $like = '%' . $location . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($guests > 0) {
    $where .= ' AND EXISTS (SELECT 1 FROM rooms r WHERE r.homestay_id = h.id AND r.is_active = 1 AND r.max_guests >= ?)';
    $params[] = $guests;
}

if ($minPrice > 0) {
    $where .= ' AND (SELECT MIN(price_per_night) FROM rooms WHERE homestay_id = h.id AND is_active = 1) >= ?';
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $where .= ' AND (SELECT MIN(price_per_night) FROM rooms WHERE homestay_id = h.id AND is_active = 1) <= ?';
    $params[] = $maxPrice;
}


// Filter by selected amenities
if (!empty($selectedAmenities) && is_array($selectedAmenities)) {
    foreach ($selectedAmenities as $amId) {
        $where .= ' AND EXISTS (SELECT 1 FROM homestay_amenities WHERE homestay_id = h.id AND amenity_id = ?)';
        $params[] = (int)$amId;
    }
}

$countStmt = $conn->prepare("SELECT COUNT(*) FROM homestays h WHERE $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "SELECT h.*,
        (SELECT MIN(price_per_night) FROM rooms WHERE homestay_id = h.id AND is_active = 1) AS min_price,
        (SELECT AVG(rating) FROM reviews WHERE homestay_id = h.id AND is_approved = 1) AS avg_rating
        FROM homestays h
        WHERE $where
        ORDER BY h.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

$amenities = $conn->query('SELECT * FROM amenities ORDER BY name')->fetchAll();

// Construct query strings for pagination links persistence
$amenityQuery = '';
if (!empty($selectedAmenities) && is_array($selectedAmenities)) {
    foreach ($selectedAmenities as $amId) {
        $amenityQuery .= '&amenity[]=' . (int)$amId;
    }
}
$queryString = '?location=' . urlencode($location) . '&guests=' . $guests . '&min_price=' . $minPrice . '&max_price=' . $maxPrice . $amenityQuery;

$pageTitle = 'Search';
require __DIR__ . '/../includes/header.php';
?>
<!-- Search Page Header -->
<div class="page-header-bar animate__animated animate__fadeIn">
    <div class="container">
        <h1 class="display-font fw-bold text-teal-deep mb-1">Search Stays in West Sikkim</h1>
        <p class="text-muted mb-0"><?= $total ?> properties matching your preferences</p>
    </div>
</div>

<div class="container pb-5 animate__animated animate__fadeIn animate__delay-1s">
    <div class="row g-4">
        <!-- Sidebar Filter panel -->
        <div class="col-lg-3">
            <form method="GET" class="filter-panel border rounded-4 shadow-sm bg-white p-4">
                <h5 class="fw-bold text-dark mb-4 display-font">Filters</h5>
                
                <!-- Location -->
                <h6 class="text-teal mb-2"><i class="fas fa-map-marker-alt me-2"></i>Location</h6>
                <input type="text" name="location" class="form-control mb-3" placeholder="Khechuperi, Pelling..." value="<?= e($location) ?>">
                
                <!-- Guests -->
                <h6 class="text-teal mb-2"><i class="fas fa-users me-2"></i>Guests</h6>
                <select name="guests" class="form-select mb-3">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                    <option value="<?= $i ?>" <?= $guests == $i ? 'selected' : '' ?>><?= $i ?> Guest<?= $i > 1 ? 's' : '' ?></option>
                    <?php endfor; ?>
                </select>
                
                <!-- Price -->
                <h6 class="text-teal mb-2"><i class="fas fa-rupee-sign me-2"></i>Price Range</h6>
                <div class="row g-2 mb-3">
                    <div class="col-6"><input type="number" name="min_price" class="form-control text-center" placeholder="Min" value="<?= $minPrice ?: '' ?>"></div>
                    <div class="col-6"><input type="number" name="max_price" class="form-control text-center" placeholder="Max" value="<?= $maxPrice ?: '' ?>"></div>
                </div>

                
                <!-- Amenities dynamic list -->
                <?php if (!empty($amenities)): ?>
                <h6 class="text-teal mb-2"><i class="fas fa-wifi me-2"></i>Amenities</h6>
                <div class="d-flex flex-column gap-2 mb-4">
                    <?php foreach ($amenities as $a):
                        $checked = in_array($a['id'], $selectedAmenities) ? 'checked' : '';
                    ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="amenity[]" value="<?= (int)$a['id'] ?>" id="am_<?= $a['id'] ?>" <?= $checked ?>>
                        <label class="form-check-label small text-muted" for="am_<?= $a['id'] ?>"><?= e($a['name']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary btn-teal w-100 mb-2 py-2 fw-semibold"><i class="fas fa-search me-1.5"></i> Apply Filters</button>
                <a href="<?= BASE_URL ?>pages/search.php" class="btn btn-outline-secondary w-100 py-2 fw-semibold"><i class="fas fa-rotate-left me-1.5"></i> Reset</a>
            </form>
        </div>

        <!-- Result List Column -->
        <div class="col-lg-9">
            <div class="row g-4">
                <?php if (empty($results)): ?>
                    <div class="col-12 text-center py-5 border rounded-4 bg-light bg-opacity-25 shadow-xs">
                        <div class="d-inline-flex align-items-center justify-content-center bg-teal bg-opacity-10 text-teal rounded-circle mb-3" style="width: 56px; height: 56px;">
                            <i class="fas fa-magnifying-glass-minus fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">No homestays found</h5>
                        <p class="text-muted small mb-3">Try adjusting your filters or search keywords to find available Sikkim stays.</p>
                        <a href="<?= BASE_URL ?>pages/search.php" class="btn btn-teal btn-sm fw-bold px-3">Clear all filters</a>
                    </div>
                <?php else: foreach ($results as $h): ?>
                    <div class="col-sm-6 col-xl-4"><?= homestay_card($h) ?></div>
                <?php endforeach; endif; ?>
            </div>

            <!-- Custom Rounded Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center gap-1.5">
                    <!-- Prev Page -->
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link border rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px" href="<?= $queryString ?>&page=<?= $page - 1 ?>"><i class="fas fa-chevron-left small"></i></a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link border rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px" href="<?= $queryString ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <!-- Next Page -->
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link border rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px" href="<?= $queryString ?>&page=<?= $page + 1 ?>"><i class="fas fa-chevron-right small"></i></a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
