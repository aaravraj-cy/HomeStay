<?php
// Homestay details
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in() && is_owner()) {
    redirect(BASE_URL . 'owner/dashboard.php');
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT h.*, o.business_name, u.full_name AS owner_name, u.profile_image AS owner_image
    FROM homestays h
    JOIN owners o ON o.id = h.owner_id
    JOIN users u ON u.id = o.user_id
    WHERE h.id = ? AND h.is_active = 1");
$stmt->execute([$id]);
$h = $stmt->fetch();

if (!$h) {
    set_flash('error', 'Homestay not found.');
    redirect(BASE_URL . 'pages/search.php');
}

$rooms = $conn->prepare('SELECT * FROM rooms WHERE homestay_id = ? AND is_active = 1 ORDER BY price_per_night');
$rooms->execute([$id]);
$rooms = $rooms->fetchAll();

$ams = $conn->prepare('SELECT a.* FROM amenities a JOIN homestay_amenities ha ON ha.amenity_id = a.id WHERE ha.homestay_id = ?');
$ams->execute([$id]);
$ams = $ams->fetchAll();

$reviews = $conn->prepare('SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.homestay_id = ? AND r.is_approved = 1 ORDER BY r.created_at DESC');
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();
$avgStmt = $conn->prepare('SELECT AVG(rating) FROM reviews WHERE homestay_id = ? AND is_approved = 1');
$avgStmt->execute([$id]);
$avgRating = (float)$avgStmt->fetchColumn();

$minPrice = 0;
if ($rooms) {
    $minPrice = min(array_column($rooms, 'price_per_night'));
}

// Fetch all homestay images
$images = [];
if (!empty($h['cover_image'])) {
    $images[] = $h['cover_image'];
}
$imgQuery = $conn->prepare("SELECT image_path FROM homestay_images WHERE homestay_id = ? ORDER BY sort_order");
$imgQuery->execute([$id]);
$extraImages = $imgQuery->fetchAll(PDO::FETCH_COLUMN);
foreach ($extraImages as $img) {
    if ($img !== $h['cover_image']) {
        $images[] = $img;
    }
}
if (empty($images)) {
    $images[] = '';
}

$pageTitle = $h['title'];
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>pages/search.php">Explore</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($h['title']) ?></li>
        </ol>
    </nav>
    
    <h1 class="display-font h2 mb-2"><?= e($h['title']) ?></h1>
    <p class="text-muted"><i class="fas fa-map-marker-alt text-danger"></i> <?= e($h['city']) ?>, <?= e($h['state']) ?> · <?= stars($avgRating) ?></p>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <!-- Swiper Image Slider -->
            <div class="swiper detail-swiper rounded-4 overflow-hidden shadow-sm" style="background: var(--sn-sand-dark)">
                <div class="swiper-wrapper">
                    <?php foreach ($images as $img):
                        // Resolve full image URL
                        if ($img && file_exists(UPLOAD_HOMESTAYS . $img)) {
                            $imgUrl = BASE_URL . 'assets/uploads/homestays/' . $img;
                        } else {
                            $imgUrl = display_image(['id' => $id, 'city' => $h['city'], 'cover_image' => $img]);
                        }
                    ?>
                    <div class="swiper-slide">
                        <img src="<?= e($imgUrl) ?>" class="w-100" style="height:480px; object-fit:cover" alt="<?= e($h['title']) ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($images) > 1): ?>
                <div class="swiper-button-prev detail-prev"></div>
                <div class="swiper-button-next detail-next"></div>
                <div class="swiper-pagination"></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="booking-widget border">
                <div class="d-flex align-items-baseline gap-2 mb-3">
                    <span class="fs-3 fw-bold text-teal"><?= money($minPrice) ?></span>
                    <span class="text-muted">/ night</span>
                </div>
                <form action="<?= BASE_URL ?>pages/book.php" method="GET">
                    <input type="hidden" name="homestay_id" value="<?= $id ?>">
                    <div class="mb-2">
                        <label class="form-label">Room</label>
                        <select name="room_id" class="form-select" required>
                            <option value="">Select</option>
                            <?php foreach ($rooms as $r): ?>
                            <option value="<?= (int)$r['id'] ?>"><?= e($r['name']) ?> - <?= money($r['price_per_night']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Check-out</label><input type="date" name="check_out" class="form-control" required></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Guests</label>
                        <select name="guests" class="form-select">
                            <?php for ($i = 1; $i <= 8; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
                        </select>
                    </div>
                    <button class="btn btn-primary w-100">Book now</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h4>About</h4>
            <p class="text-muted"><?= nl2br(e($h['description'])) ?></p>

            <?php if ($ams): ?>
            <h4 class="mt-4">Amenities</h4>
            <div class="amenity-grid mb-4">
                <?php foreach ($ams as $a): ?>
                <div class="amenity-item"><i class="fas <?= e($a['icon']) ?>"></i> <?= e($a['name']) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <h4>Rooms</h4>
            <?php foreach ($rooms as $r): ?>
            <div class="room-card mb-2">
                <strong><?= e($r['name']) ?></strong>
                <div class="small text-muted"><?= e($r['room_type']) ?> · <?= (int)$r['max_guests'] ?> guests</div>
                <div><?= money($r['price_per_night']) ?> / night</div>
            </div>
            <?php endforeach; ?>

            <h4 class="fw-bold text-dark mt-4 mb-3"><i class="fas fa-star text-warning me-2"></i>Guest Reviews (<?= count($reviews) ?>)</h4>

            <?php if (empty($reviews)): ?>
                <div class="card p-3 text-center bg-light border">
                    <p class="text-muted small mb-0">No guest reviews yet. Be the first traveler to stay here and leave a review!</p>
                </div>
            <?php else: foreach ($reviews as $rv): ?>
                <div class="card p-3 mb-2 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-dark small"><?= e($rv['full_name']) ?></strong>
                        <div><?= stars((float)$rv['rating']) ?></div>
                    </div>
                    <?php if (!empty($rv['title'])): ?>
                        <h6 class="fw-bold small mb-1"><?= e($rv['title']) ?></h6>
                    <?php endif; ?>
                    <p class="text-muted small mb-0"><?= e($rv['comment']) ?></p>
                    
                    <?php if (!empty($rv['owner_reply'])): ?>
                        <div class="bg-light p-2 mt-2 rounded border-start border-primary border-3 small">
                            <strong class="text-dark d-block mb-0.5">Host Reply:</strong>
                            <span class="text-muted"><?= e($rv['owner_reply']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>

            <div class="dash-card mt-4">
                <h5>Hosted by <?= e($h['owner_name']) ?></h5>
                <?php if ($h['business_name']): ?><p class="text-muted mb-0"><?= e($h['business_name']) ?></p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
