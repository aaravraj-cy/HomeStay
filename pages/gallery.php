<?php
// Photo Gallery page
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in() && is_owner()) {
    redirect(BASE_URL . 'owner/dashboard.php');
}

$sql = "SELECT h.*,
        (SELECT MIN(price_per_night) FROM rooms WHERE homestay_id = h.id AND is_active = 1) AS min_price,
        (SELECT AVG(rating) FROM reviews WHERE homestay_id = h.id AND is_approved = 1) AS avg_rating
        FROM homestays h
        WHERE h.is_active = 1
        ORDER BY h.created_at DESC";
$items = $conn->query($sql)->fetchAll();

// Extra gallery images from uploads
$extraImages = [];
try {
    $extraImages = $conn->query("SELECT hi.image_path, hi.homestay_id, h.title, h.city
        FROM homestay_images hi
        JOIN homestays h ON h.id = hi.homestay_id
        WHERE h.is_active = 1
        ORDER BY hi.id DESC
        LIMIT 24")->fetchAll();
} catch (Exception $e) {
    $extraImages = [];
}

// Extract unique cities represented in the gallery
$cities = [];
foreach ($items as $h) {
    if (!empty($h['city'])) {
        $cities[strtolower(trim($h['city']))] = trim($h['city']);
    }
}
foreach ($extraImages as $ex) {
    if (!empty($ex['city'])) {
        $cities[strtolower(trim($ex['city']))] = trim($ex['city']);
    }
}
ksort($cities);

// Compute photo counts per location
$cityCounts = ['all' => 0];
foreach ($items as $h) {
    if (!empty($h['city'])) {
        $slug = strtolower(trim($h['city']));
        $cityCounts[$slug] = ($cityCounts[$slug] ?? 0) + 1;
        $cityCounts['all']++;
    }
}
foreach ($extraImages as $ex) {
    if (!empty($ex['city'])) {
        $slug = strtolower(trim($ex['city']));
        $cityCounts[$slug] = ($cityCounts[$slug] ?? 0) + 1;
        $cityCounts['all']++;
    }
}

$pageTitle = 'Gallery';
require __DIR__ . '/../includes/header.php';
?>
<!-- Gallery Hero Bar -->
<div class="page-header-bar gallery-hero animate__animated animate__fadeIn">
    <div class="container text-center text-md-start">
        <nav aria-label="breadcrumb" class="mb-2 d-inline-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
                <li class="breadcrumb-item active">Gallery</li>
            </ol>
        </nav>
        <h1 class="display-font fw-bold text-teal-deep mb-2">Photo Gallery</h1>
        <p class="text-muted mb-0">Explore beautiful local spaces curated by our hosts</p>
    </div>
</div>

<section class="section pt-0 animate__animated animate__fadeIn animate__delay-1s">
    <div class="container">
        <?php if (empty($items) && empty($extraImages)): ?>
            <div class="empty-state py-5 border rounded-4 bg-light bg-opacity-25" data-aos="fade-up">
                <i class="far fa-image text-muted fs-1 mb-3"></i>
                <p class="text-muted">No photos uploaded yet. Stays will appear here once owners publish them.</p>
                <a href="<?= BASE_URL ?>pages/search.php" class="btn btn-primary btn-sm px-4">Explore stays</a>
            </div>
        <?php else: ?>
            <!-- Dynamic Location Filters -->
            <div class="d-flex flex-wrap justify-content-center gap-2 mb-5 bg-light p-2 rounded-pill border mx-auto" style="max-width: max-content">
                <button class="btn btn-sm btn-teal px-4 py-2 rounded-pill fw-semibold gallery-filter-btn" data-filter="all">
                    All Photos <span class="badge bg-white text-teal ms-1.5 rounded-pill small"><?= $cityCounts['all'] ?></span>
                </button>
                <?php foreach ($cities as $slug => $name): ?>
                    <button class="btn btn-sm btn-light border px-4 py-2 rounded-pill fw-semibold text-muted gallery-filter-btn" data-filter="<?= e($slug) ?>">
                        <?= e($name) ?> <span class="badge bg-secondary bg-opacity-15 text-muted ms-1.5 rounded-pill small"><?= $cityCounts[$slug] ?? 0 ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Gallery Grid -->
            <div class="gallery-grid" id="galleryGrid">
                <!-- Cover Images -->
                <?php foreach ($items as $i => $h):
                    $img = display_image($h);
                    $citySlug = strtolower(trim($h['city'] ?? ''));
                ?>
                <div class="gallery-card-item position-relative" data-location="<?= e($citySlug) ?>" data-aos="zoom-in" data-aos-delay="<?= min($i * 30, 150) ?>">
                    <a href="<?= BASE_URL ?>pages/homestay-details.php?id=<?= (int)$h['id'] ?>"
                       class="gallery-item <?= $i % 5 === 0 ? 'gallery-item-wide' : '' ?>">
                        <img src="<?= e($img) ?>" alt="<?= e($h['title']) ?>" loading="lazy" class="w-100 object-fit-cover rounded-4">
                        <div class="gallery-overlay">
                            <span class="gallery-city"><i class="fas fa-map-marker-alt"></i> <?= e($h['city']) ?></span>
                            <strong><?= e($h['title']) ?></strong>
                            <?php if (!empty($h['min_price'])): ?>
                            <span class="gallery-price"><?= money((float)$h['min_price']) ?> / night</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <!-- Lightbox Trigger Button -->
                    <button type="button" class="gallery-preview-btn d-flex align-items-center justify-content-center" data-lightbox-trigger data-src="<?= e($img) ?>" data-title="<?= e($h['title']) ?>" data-city="<?= e($h['city']) ?>" title="Quick preview">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <?php endforeach; ?>

                <!-- Additional Gallery Images -->
                <?php foreach ($extraImages as $ex):
                    $path = UPLOAD_HOMESTAYS . $ex['image_path'];
                    if (!file_exists($path)) continue;
                    $imgUrl = BASE_URL . 'assets/uploads/homestays/' . $ex['image_path'];
                    $citySlug = strtolower(trim($ex['city'] ?? ''));
                ?>
                <div class="gallery-card-item position-relative" data-location="<?= e($citySlug) ?>" data-aos="zoom-in">
                    <a href="<?= BASE_URL ?>pages/homestay-details.php?id=<?= (int)$ex['homestay_id'] ?>"
                       class="gallery-item">
                        <img src="<?= e($imgUrl) ?>" alt="<?= e($ex['title']) ?>" loading="lazy" class="w-100 object-fit-cover rounded-4">
                        <div class="gallery-overlay">
                            <span class="gallery-city"><i class="fas fa-map-marker-alt"></i> <?= e($ex['city']) ?></span>
                            <strong><?= e($ex['title']) ?></strong>
                        </div>
                    </a>
                    <!-- Lightbox Trigger Button -->
                    <button type="button" class="gallery-preview-btn d-flex align-items-center justify-content-center" data-lightbox-trigger data-src="<?= e($imgUrl) ?>" data-title="<?= e($ex['title']) ?>" data-city="<?= e($ex['city']) ?>" title="Quick preview">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Upgraded Lightbox Modal with Slider Arrows & Filmstrip Thumbnails -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0 position-relative">
            <!-- Close Button -->
            <button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            
            <!-- Main Photo Wrapper -->
            <div class="position-relative overflow-hidden rounded-4 shadow-lg bg-black bg-opacity-70 d-flex align-items-center justify-content-center">
                <img src="" id="lightboxImg" class="w-100 object-fit-contain" style="max-height: 60vh; transition: opacity 0.3s ease-in-out;" alt="Preview">
                
                <!-- Slide Controls -->
                <button type="button" id="lightboxPrev" class="btn btn-dark rounded-circle opacity-75 border-0 position-absolute start-0 top-50 translate-middle-y ms-3" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center; z-index: 1055" title="Previous Image">
                    <i class="fas fa-chevron-left text-white"></i>
                </button>
                <button type="button" id="lightboxNext" class="btn btn-dark rounded-circle opacity-75 border-0 position-absolute end-0 top-50 translate-middle-y me-3" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center; z-index: 1055" title="Next Image">
                    <i class="fas fa-chevron-right text-white"></i>
                </button>
            </div>
            
            <!-- Description Overlay -->
            <div class="text-white text-center mt-3 bg-dark bg-opacity-70 p-3 rounded-3" style="backdrop-filter: blur(10px)">
                <h5 id="lightboxTitle" class="mb-1 fw-bold display-font"></h5>
                <span class="small text-teal-light text-uppercase fw-semibold" id="lightboxCity"><i class="fas fa-map-marker-alt me-1"></i></span>
            </div>
            
            <!-- Horizontal Filmstrip Thumbnails -->
            <div class="d-flex justify-content-center gap-2 mt-3 overflow-x-auto py-2 px-1" id="lightboxFilmstrip" style="scrollbar-width: none; -ms-overflow-style: none;">
                <!-- Dynamically populated in JS -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('lightboxModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    var modal = new bootstrap.Modal(modalEl);
    
    // Filtering Logic
    var filterButtons = document.querySelectorAll('.gallery-filter-btn');
    var galleryCards = document.querySelectorAll('.gallery-card-item');
    
    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            // Toggle active styles
            filterButtons.forEach(b => {
                b.classList.remove('btn-teal');
                b.classList.add('btn-light', 'text-muted');
                
                // Toggle badge colors too
                var badge = b.querySelector('.badge');
                if (badge) {
                    badge.classList.remove('bg-white', 'text-teal');
                    badge.classList.add('bg-secondary', 'bg-opacity-15', 'text-muted');
                }
            });
            
            this.classList.add('btn-teal');
            this.classList.remove('btn-light', 'text-muted');
            var myBadge = this.querySelector('.badge');
            if (myBadge) {
                myBadge.classList.add('bg-white', 'text-teal');
                myBadge.classList.remove('bg-secondary', 'bg-opacity-15', 'text-muted');
            }
            
            var filterValue = this.getAttribute('data-filter');
            
            galleryCards.forEach(function (card) {
                if (filterValue === 'all' || card.getAttribute('data-location') === filterValue) {
                    card.style.display = 'block';
                    card.classList.add('animate__animated', 'animate__fadeIn');
                } else {
                    card.style.display = 'none';
                    card.classList.remove('animate__animated', 'animate__fadeIn');
                }
            });
            // Update active triggers for slider
            updateActiveTriggers();
        });
    });

    // Lightbox Slider Logic
    var activeTriggers = [];
    var currentIndex = 0;
    var lightboxImg = document.getElementById('lightboxImg');
    
    function updateActiveTriggers() {
        activeTriggers = [];
        document.querySelectorAll('.gallery-card-item').forEach(function (card) {
            if (card.style.display !== 'none') {
                var btn = card.querySelector('[data-lightbox-trigger]');
                if (btn) activeTriggers.push(btn);
            }
        });
        
        // Rebuild filmstrip thumbnails
        var filmstrip = document.getElementById('lightboxFilmstrip');
        filmstrip.innerHTML = '';
        activeTriggers.forEach(function (trigger, idx) {
            var thumb = document.createElement('img');
            thumb.src = trigger.getAttribute('data-src');
            thumb.className = 'rounded border border-2 border-transparent cursor-pointer object-fit-cover';
            thumb.style.width = '60px';
            thumb.style.height = '45px';
            thumb.style.transition = 'all 0.2s ease';
            thumb.style.opacity = '0.5';
            
            thumb.addEventListener('click', function () {
                showImage(idx);
            });
            
            filmstrip.appendChild(thumb);
        });
    }
    
    function showImage(index) {
        if (index < 0 || index >= activeTriggers.length) return;
        currentIndex = index;
        var trigger = activeTriggers[index];
        
        // Smooth Fade Transition
        lightboxImg.style.opacity = '0';
        setTimeout(function() {
            lightboxImg.src = trigger.getAttribute('data-src');
            document.getElementById('lightboxTitle').textContent = trigger.getAttribute('data-title') || '';
            document.getElementById('lightboxCity').innerHTML = '<i class="fas fa-map-marker-alt me-1"></i>' + (trigger.getAttribute('data-city') || '');
            lightboxImg.style.opacity = '1';
            
            // Highlight active filmstrip thumbnail
            var thumbs = document.querySelectorAll('#lightboxFilmstrip img');
            thumbs.forEach(function (t, idx) {
                if (idx === index) {
                    t.style.opacity = '1';
                    t.style.borderColor = '#2dd4bf'; // Active teal color
                    t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                } else {
                    t.style.opacity = '0.5';
                    t.style.borderColor = 'transparent';
                }
            });
        }, 150);
    }
    
    // Initial triggers list setup
    updateActiveTriggers();
    
    // Register click handlers on trigger buttons
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-lightbox-trigger]');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            
            updateActiveTriggers();
            
            var idx = activeTriggers.indexOf(trigger);
            if (idx !== -1) {
                showImage(idx);
                modal.show();
            }
        }
    });
    
    // Previous & Next bindings
    document.getElementById('lightboxPrev').addEventListener('click', function () {
        var nextIdx = currentIndex - 1;
        if (nextIdx < 0) nextIdx = activeTriggers.length - 1;
        showImage(nextIdx);
    });
    
    document.getElementById('lightboxNext').addEventListener('click', function () {
        var nextIdx = currentIndex + 1;
        if (nextIdx >= activeTriggers.length) nextIdx = 0;
        showImage(nextIdx);
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function (e) {
        if (!modalEl.classList.contains('show')) return;
        if (e.key === 'ArrowLeft') {
            document.getElementById('lightboxPrev').click();
        } else if (e.key === 'ArrowRight') {
            document.getElementById('lightboxNext').click();
        }
    });
});
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
