<?php
// Home page
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in() && is_owner()) {
    redirect(BASE_URL . 'owner/dashboard.php');
}

// Featured / latest homestays
$sql = "SELECT h.*,
        (SELECT MIN(price_per_night) FROM rooms WHERE homestay_id = h.id AND is_active = 1) AS min_price,
        (SELECT AVG(rating) FROM reviews WHERE homestay_id = h.id AND is_approved = 1) AS avg_rating
        FROM homestays h
        WHERE h.is_active = 1
        ORDER BY h.is_featured DESC, h.created_at DESC
        LIMIT 8";
$homestays = $conn->query($sql)->fetchAll();

// Cities for destinations
$cities = $conn->query("SELECT city, state, COUNT(*) AS total FROM homestays WHERE is_active = 1 GROUP BY city, state LIMIT 6")->fetchAll();

$pageTitle = 'Home';
require __DIR__ . '/includes/header.php';

$city_photos = [
    'khechuperi' => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=600&q=80',
    'pelling'    => 'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?w=600&q=80',
    'yuksom'     => 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=600&q=80',
    'geyzing'    => 'https://images.unsplash.com/photo-1605649487212-47bdab064df7?w=600&q=80',
    'sikkim'     => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=600&q=80',
];
?>

<section class="hero">
    <div class="container">
        <div class="hero-content" data-aos="fade-right">
            <div class="hero-brand"><?= e(APP_NAME) ?> Sikkim</div>
            <p class="hero-tagline">Experience the magic of Khechuperi, West Sikkim. Stay with locals.</p>
            <form action="<?= BASE_URL ?>pages/search.php" method="GET" class="search-bar position-relative shadow-lg border rounded-4 bg-white p-2" style="width: 1000px; margin: 0 auto;">
                <div class="row g-0 align-items-center">
                    <!-- Location Field -->
                    <div class="col-md-3 search-field px-3 py-2 position-relative" style="transition: background 0.25s ease;">
                        <label class="form-label small fw-bold text-teal d-flex align-items-center gap-1.5"><i class="fas fa-map-marker-alt text-teal"></i> Location</label>
                        <input type="text" name="location" id="searchLocation" class="form-control ps-0" placeholder="Khechuperi, Pelling, Yuksom..." autocomplete="off" style="font-size: 0.95rem;">
                        <!-- Autocomplete dropdown -->
                        <div class="search-suggestions shadow-sm border mt-1" id="searchSuggestions">
                            <div class="px-3 py-2 text-muted fw-bold small text-uppercase bg-light" style="letter-spacing: 0.04em; font-size: 0.7rem">Sikkim Hubs</div>
                            <a href="#" data-value="Khechuperi" class="text-decoration-none"><i class="fas fa-map-pin text-teal me-2"></i>Khechuperi, West Sikkim</a>
                            <a href="#" data-value="Pelling" class="text-decoration-none"><i class="fas fa-map-pin text-teal me-2"></i>Pelling, West Sikkim</a>
                            <a href="#" data-value="Yuksom" class="text-decoration-none"><i class="fas fa-map-pin text-teal me-2"></i>Yuksom, West Sikkim</a>
                            <a href="#" data-value="Geyzing" class="text-decoration-none"><i class="fas fa-map-pin text-teal me-2"></i>Geyzing, West Sikkim</a>
                        </div>
                    </div>
                    
                    <!-- Check-in Field -->
                    <div class="col-6 col-md-2 search-field px-3 py-2" style="transition: background 0.25s ease;">
                        <label class="form-label small fw-bold text-teal d-flex align-items-center gap-1.5"><i class="far fa-calendar-plus text-teal"></i> Check-in</label>
                        <input type="date" name="check_in" id="checkInDate" class="form-control ps-0" min="<?= date('Y-m-d') ?>" style="font-size: 0.95rem;">
                    </div>
                    
                    <!-- Check-out Field -->
                    <div class="col-6 col-md-2 search-field px-3 py-2" style="transition: background 0.25s ease;">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="form-label small fw-bold text-teal d-flex align-items-center gap-1.5 mb-0"><i class="far fa-calendar-minus text-teal"></i> Check-out</label>
                            <span id="searchNightsCount" class="badge bg-teal bg-opacity-10 text-teal rounded-pill d-none" style="font-size: 0.65rem; padding: 0.25em 0.5em;">1 night</span>
                        </div>
                        <input type="date" name="check_out" id="checkOutDate" class="form-control ps-0" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" style="font-size: 0.95rem; margin-top: 0.25rem;">
                    </div>
                    
                    <!-- Guests Selector -->
                    <div class="col-6 col-md-2 search-field px-3 py-2 position-relative" style="transition: background 0.25s ease;">
                        <label class="form-label small fw-bold text-teal d-flex align-items-center gap-1.5"><i class="fas fa-user-friends text-teal"></i> Guests</label>
                        
                        <div class="guests-dropdown-wrap">
                            <button type="button" id="guestsTriggerBtn" class="form-select ps-0 fw-semibold text-start border-0 bg-transparent w-100 shadow-none d-flex align-items-center justify-content-between" style="font-size: 0.95rem; height: 38px; padding-right: 1.5rem;">
                                <span id="guestsCountLabel">1 Guest</span>
                            </button>
                            <input type="hidden" name="guests" id="guestsHiddenInput" value="1">
                            
                            <!-- Custom Popover Card -->
                            <div class="card p-3 shadow-lg border rounded-3 position-absolute start-0 mt-2 animate__animated animate__fadeIn" id="guestsPopover" style="width: 250px; z-index: 1000; display: none;">
                                <!-- Adults -->
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <strong class="text-dark small d-block">Adults</strong>
                                        <span class="text-muted" style="font-size: 0.7rem;">Age 13 or above</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center" id="btnDecAdults" style="width:28px; height:28px; padding:0"><i class="fas fa-minus small"></i></button>
                                        <span class="fw-bold text-dark small" id="valAdults">1</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center" id="btnIncAdults" style="width:28px; height:28px; padding:0"><i class="fas fa-plus small"></i></button>
                                    </div>
                                </div>
                                <!-- Children -->
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <strong class="text-dark small d-block">Children</strong>
                                        <span class="text-muted" style="font-size: 0.7rem;">Ages 2 – 12</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center" id="btnDecChildren" style="width:28px; height:28px; padding:0"><i class="fas fa-minus small"></i></button>
                                        <span class="fw-bold text-dark small" id="valChildren">0</span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center" id="btnIncChildren" style="width:28px; height:28px; padding:0"><i class="fas fa-plus small"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit CTA -->
                    <div class="col-6 col-md-3 p-2">
                        <button type="submit" class="btn btn-search w-100 d-flex align-items-center justify-content-center gap-2 py-2.5 rounded-3 fw-bold shadow-xs"><i class="fas fa-search"></i> Search Stays</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<section class="section" id="featured">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Homestays</h2>
            <p>Browse available stays</p>
        </div>
        <div class="row g-4">
            <?php if (empty($homestays)): ?>
                <div class="col-12">
                    <div class="empty-state" data-aos="fade-up">
                        <i class="fas fa-home"></i>
                        <p>No homestays yet. Owner can add properties after registration.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($homestays as $h): ?>
                <div class="col-sm-6 col-md-4 col-lg-3"><?= homestay_card($h) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="<?= BASE_URL ?>pages/search.php" class="btn btn-outline-primary px-4">View all homestays</a>
        </div>
    </div>
</section>

<!-- <?php if (!empty($cities)): ?>
<section class="section section-alt" id="destinations">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Featured Destinations</h2>
            <p>Explore stays in these popular cities</p>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($cities as $i => $c):
                $cityName = strtolower(trim($c['city']));
                $img = $city_photos[$cityName] ?? 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=600&q=80';
            ?>
            <div class="col-6 col-md-4 col-lg-2" data-aos="zoom-in" data-aos-delay="<?= min($i * 50, 300) ?>">
                <a href="<?= BASE_URL ?>pages/search.php?location=<?= urlencode($c['city']) ?>" class="dest-card d-block">
                    <img src="<?= e($img) ?>" alt="<?= e($c['city']) ?>" loading="lazy">
                    <div class="dest-overlay">
                        <h4><?= e($c['city']) ?></h4>
                        <span><?= (int)$c['total'] ?> stay<?= $c['total'] > 1 ? 's' : '' ?></span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?> -->

<!-- Verified Guest Reviews Section -->
<?php
$homeReviews = [];
try {
    $rStmt = $conn->query("SELECT r.*, u.full_name, u.profile_image, h.title AS homestay_title, h.city 
                           FROM reviews r 
                           JOIN users u ON u.id = r.user_id 
                           JOIN homestays h ON h.id = r.homestay_id 
                           WHERE r.is_approved = 1 
                           ORDER BY r.rating DESC, r.created_at DESC LIMIT 3");
    if ($rStmt) {
        $homeReviews = $rStmt->fetchAll();
    }
} catch (Exception $e) {
        $homeReviews = [];
}
?>
<!-- Simple Guest Reviews Section (BCA Level) -->
<?php
$homeReviews = [];
try {
    $rStmt = $conn->query("SELECT r.*, u.full_name, h.title AS homestay_title, h.city 
                           FROM reviews r 
                           JOIN users u ON r.user_id = u.id 
                           JOIN homestays h ON r.homestay_id = h.id 
                           WHERE r.is_approved = 1 
                           ORDER BY r.created_at DESC LIMIT 3");
    if ($rStmt) {
        $homeReviews = $rStmt->fetchAll();
    }
} catch (Exception $e) {
    $homeReviews = [];
}
?>
<section class="section bg-light py-5" id="guest-reviews">
    <div class="container py-3">
        <div class="text-center mb-5" data-aos="fade-up">
            <!-- <span class="badge bg-teal bg-opacity-10 text-teal border border-teal border-opacity-20 px-3 py-1.5 rounded-pill font-monospace small mb-2">
                <i class="fas fa-star text-warning me-1.5"></i>VERIFIED REVIEWS
            </span> -->
            <h2 class="fw-bold text-dark h2 mb-2">What Our Guests Say</h2>
            <p class="text-muted mb-0">Real experiences from travelers staying in West Sikkim homestays</p>
        </div>

        <div class="row g-4">
            <?php if (!empty($homeReviews)): ?>
                <?php foreach ($homeReviews as $r): ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-white hover-shadow transition" data-aos="fade-up">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="mb-2 text-warning">
                                    <?= stars((float)$r['rating']) ?>
                                </div>
                                <?php if (!empty($r['title'])): ?>
                                    <h6 class="fw-bold text-dark fs-6 mb-2"><?= e($r['title']) ?></h6>
                                <?php endif; ?>
                                <p class="text-secondary small mb-4" style="line-height: 1.6">"<?= e($r['comment']) ?>"</p>
                            </div>
                            
                            <div class="pt-3 border-top d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-teal bg-opacity-10 text-teal fw-bold d-flex align-items-center justify-content-center border border-teal border-opacity-20" style="width: 40px; height: 40px; min-width: 40px;">
                                    <?= strtoupper(substr($r['full_name'], 0, 1)) ?>
                                </div>
                                <div class="overflow-hidden">
                                    <strong class="d-block text-dark small text-truncate mb-0"><?= e($r['full_name']) ?></strong>
                                    <small class="text-muted d-block text-truncate">Stayed at <span class="fw-semibold text-dark"><?= e($r['homestay_title']) ?></span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Clean Fallback Guest Review Cards -->
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-white hover-shadow transition" data-aos="fade-up" data-aos-delay="0">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="mb-2 text-warning">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <h6 class="fw-bold text-dark fs-6 mb-2">A magical stay near Khechuperi Lake!</h6>
                                <p class="text-secondary small mb-4" style="line-height: 1.6">"The host family treated us like their own. We enjoyed homemade local food and warm tea by the fireplace. Highly recommended!"</p>
                            </div>
                            
                            <div class="pt-3 border-top d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-teal bg-opacity-10 text-teal fw-bold d-flex align-items-center justify-content-center border border-teal border-opacity-20" style="width: 40px; height: 40px; min-width: 40px;">
                                    P
                                </div>
                                <div class="overflow-hidden">
                                    <strong class="d-block text-dark small text-truncate mb-0">Priya Sharma</strong>
                                    <small class="text-muted d-block text-truncate">Stayed at <span class="fw-semibold text-dark">Cherry Blossom Homestay</span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-white hover-shadow transition" data-aos="fade-up" data-aos-delay="100">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="mb-2 text-warning">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <h6 class="fw-bold text-dark fs-6 mb-2">Peaceful and authentic nature</h6>
                                <p class="text-secondary small mb-4" style="line-height: 1.6">"If you want to escape noisy city life, this is paradise. Clean rooms and great views of the mountain valley."</p>
                            </div>
                            
                            <div class="pt-3 border-top d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-teal bg-opacity-10 text-teal fw-bold d-flex align-items-center justify-content-center border border-teal border-opacity-20" style="width: 40px; height: 40px; min-width: 40px;">
                                    D
                                </div>
                                <div class="overflow-hidden">
                                    <strong class="d-block text-dark small text-truncate mb-0">David L.</strong>
                                    <small class="text-muted d-block text-truncate">Stayed at <span class="fw-semibold text-dark">Wishing Lake Eco Cabin</span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 bg-white hover-shadow transition" data-aos="fade-up" data-aos-delay="200">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="mb-2 text-warning">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </div>
                                <h6 class="fw-bold text-dark fs-6 mb-2">Rustic comfort with local warmth</h6>
                                <p class="text-secondary small mb-4" style="line-height: 1.6">"Incredible views of Kanchenjunga right from the balcony! The host guided us on local village trails."</p>
                            </div>
                            
                            <div class="pt-3 border-top d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-teal bg-opacity-10 text-teal fw-bold d-flex align-items-center justify-content-center border border-teal border-opacity-20" style="width: 40px; height: 40px; min-width: 40px;">
                                    T
                                </div>
                                <div class="overflow-hidden">
                                    <strong class="d-block text-dark small text-truncate mb-0">Tenzin & Sonam</strong>
                                    <small class="text-muted d-block text-truncate">Stayed at <span class="fw-semibold text-dark">Valley View Mountain Retreat</span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section" id="why-us">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <h2>Why Sonam Homestay</h2>
            <p>We provide the best experiences for your comfort</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-block h-100" data-aos="fade-up" data-aos-delay="0">
                    <div class="feature-icon mx-auto"><i class="fas fa-house-user"></i></div>
                    <h4>Local Hosts</h4>
                    <p class="text-muted mb-0">Stay with verified local hosts and experience their rich hospitality.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-block h-100" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-icon mx-auto"><i class="fas fa-shield-halved"></i></div>
                    <h4>Easy Booking</h4>
                    <p class="text-muted mb-0">Quick booking confirmation with secure and simple billing procedures.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-block h-100" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-icon mx-auto"><i class="fas fa-star"></i></div>
                    <h4>Guest Reviews</h4>
                    <p class="text-muted mb-0">Transparent review system to choose the best possible stay options.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Autocomplete, Date Validation & Highlight JS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Location Autocomplete
    var locInput = document.getElementById('searchLocation');
    var sugBox = document.getElementById('searchSuggestions');
    
    if (locInput && sugBox) {
        locInput.addEventListener('focus', function () {
            sugBox.classList.add('show');
        });
        document.addEventListener('click', function (e) {
            if (!locInput.contains(e.target) && !sugBox.contains(e.target)) {
                sugBox.classList.remove('show');
            }
        });
        sugBox.querySelectorAll('a').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                locInput.value = this.getAttribute('data-value');
                sugBox.classList.remove('show');
            });
        });
        locInput.addEventListener('input', function () {
            var filter = this.value.toLowerCase();
            var hasMatches = false;
            sugBox.querySelectorAll('a').forEach(function (item) {
                var txt = item.textContent.toLowerCase();
                if (txt.indexOf(filter) > -1) {
                    item.style.display = 'block';
                    hasMatches = true;
                } else {
                    item.style.display = 'none';
                }
            });
            if (hasMatches) {
                sugBox.classList.add('show');
            } else {
                sugBox.classList.remove('show');
            }
        });
    }
    
    // Check-in / Check-out & Nights Count
    var checkIn = document.getElementById('checkInDate');
    var checkOut = document.getElementById('checkOutDate');
    var nightsBadge = document.getElementById('searchNightsCount');
    
    function calculateNights() {
        if (checkIn.value && checkOut.value) {
            var d1 = new Date(checkIn.value);
            var d2 = new Date(checkOut.value);
            if (d2 > d1) {
                var diff = Math.ceil(Math.abs(d2 - d1) / (1000 * 60 * 60 * 24));
                nightsBadge.textContent = diff + ' night' + (diff > 1 ? 's' : '');
                nightsBadge.classList.remove('d-none');
                return;
            }
        }
        nightsBadge.classList.add('d-none');
    }
    
    if (checkIn && checkOut) {
        checkIn.addEventListener('change', function () {
            if (this.value) {
                var d = new Date(this.value);
                d.setDate(d.getDate() + 1);
                var minDate = d.toISOString().split('T')[0];
                checkOut.min = minDate;
                if (checkOut.value && checkOut.value < minDate) {
                    checkOut.value = minDate;
                }
            }
            calculateNights();
        });
        checkOut.addEventListener('change', calculateNights);
    }
    
    // Custom Guest Popover Counter
    var popover = document.getElementById('guestsPopover');
    var trigger = document.getElementById('guestsTriggerBtn');
    var label = document.getElementById('guestsCountLabel');
    var hiddenInput = document.getElementById('guestsHiddenInput');
    
    var adults = 1;
    var children = 0;
    var maxGuests = 8;
    
    function updateGuestsDisplay() {
        var total = adults + children;
        label.textContent = total + ' Guest' + (total > 1 ? 's' : '');
        hiddenInput.value = total;
        
        document.getElementById('valAdults').textContent = adults;
        document.getElementById('valChildren').textContent = children;
        
        // Disable/enable controls based on bounds
        document.getElementById('btnDecAdults').disabled = (adults <= 1);
        document.getElementById('btnDecChildren').disabled = (children <= 0);
        document.getElementById('btnIncAdults').disabled = (total >= maxGuests);
        document.getElementById('btnIncChildren').disabled = (total >= maxGuests);
    }
    
    if (trigger && popover) {
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            popover.style.display = (popover.style.display === 'none' || popover.style.display === '') ? 'block' : 'none';
        });
        
        document.addEventListener('click', function (e) {
            if (!popover.contains(e.target) && !trigger.contains(e.target)) {
                popover.style.display = 'none';
            }
        });
        
        document.getElementById('btnDecAdults').addEventListener('click', function (e) {
            e.stopPropagation();
            if (adults > 1) { adults--; updateGuestsDisplay(); }
        });
        document.getElementById('btnIncAdults').addEventListener('click', function (e) {
            e.stopPropagation();
            if (adults + children < maxGuests) { adults++; updateGuestsDisplay(); }
        });
        document.getElementById('btnDecChildren').addEventListener('click', function (e) {
            e.stopPropagation();
            if (children > 0) { children--; updateGuestsDisplay(); }
        });
        document.getElementById('btnIncChildren').addEventListener('click', function (e) {
            e.stopPropagation();
            if (adults + children < maxGuests) { children++; updateGuestsDisplay(); }
        });
        
        updateGuestsDisplay();
    }
    
    // Dynamic field focus highlights (on inputs, selects, and trigger buttons)
    document.querySelectorAll('.search-field input, .search-field select, .search-field button').forEach(function (el) {
        var parent = el.closest('.search-field');
        if (parent) {
            el.addEventListener('focus', function () {
                parent.style.background = 'rgba(13, 148, 136, 0.05)';
                parent.style.borderRadius = '0.75rem';
            });
            el.addEventListener('blur', function () {
                setTimeout(function() {
                    if (document.activeElement !== el && !parent.contains(document.activeElement)) {
                        parent.style.background = 'transparent';
                    }
                }, 100);
            });
        }
    });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
