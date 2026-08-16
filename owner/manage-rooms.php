<?php
require_once __DIR__ . '/../includes/functions.php';
require_login('owner');
$ownerId = get_owner_id();

$homestays = $conn->prepare('SELECT id, title FROM homestays WHERE owner_id = ?');
$homestays->execute([$ownerId]);
$homestays = $homestays->fetchAll();

$homestayId = (int)($_GET['homestay_id'] ?? ($homestays[0]['id'] ?? 0));
$error = '';

if ($homestayId) {
    $own = $conn->prepare('SELECT id, title FROM homestays WHERE id=? AND owner_id=?');
    $own->execute([$homestayId, $ownerId]);
    $current = $own->fetch();
    if (!$current) {
        set_flash('error', 'Invalid property selection.');
        redirect(BASE_URL . 'owner/manage-rooms.php');
    }
} else {
    $current = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';
    $hid = (int)($_POST['homestay_id'] ?? 0);
    $chk = $conn->prepare('SELECT id FROM homestays WHERE id=? AND owner_id=?');
    $chk->execute([$hid, $ownerId]);
    if (!$chk->fetch()) {
        set_flash('error', 'Access denied.');
        redirect(BASE_URL . 'owner/manage-rooms.php');
    }

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['room_type'] ?? 'Private Room');
        $maxG = max(1, (int)($_POST['max_guests'] ?? 2));
        $beds = max(1, (int)($_POST['beds'] ?? 1));
        $price = (float)($_POST['price_per_night'] ?? 0);
        $cleaning = (float)($_POST['cleaning_fee'] ?? 0);
        $desc = trim($_POST['description'] ?? '');

        if ($name == '' || $price <= 0) {
            $error = 'Room name and price per night are required.';
        } elseif ($action === 'add') {
            $conn->prepare('INSERT INTO rooms (homestay_id, name, description, room_type, max_guests, beds, price_per_night, cleaning_fee) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$hid, $name, $desc ?: null, $type, $maxG, $beds, $price, $cleaning]);
            set_flash('success', 'Room added successfully.');
            redirect(BASE_URL . 'owner/manage-rooms.php?homestay_id=' . $hid);
        } else {
            $rid = (int)($_POST['room_id'] ?? 0);
            $conn->prepare('UPDATE rooms SET name=?, description=?, room_type=?, max_guests=?, beds=?, price_per_night=?, cleaning_fee=? WHERE id=? AND homestay_id=?')
                ->execute([$name, $desc ?: null, $type, $maxG, $beds, $price, $cleaning, $rid, $hid]);
            set_flash('success', 'Room updated successfully.');
            redirect(BASE_URL . 'owner/manage-rooms.php?homestay_id=' . $hid);
        }
    }

    if ($action === 'delete') {
        $rid = (int)($_POST['room_id'] ?? 0);
        $active = $conn->prepare("SELECT COUNT(*) FROM booking_details bd JOIN bookings b ON b.id=bd.booking_id WHERE bd.room_id=? AND b.status IN ('pending','confirmed')");
        $active->execute([$rid]);
        if ((int)$active->fetchColumn() > 0) {
            set_flash('error', 'Cannot delete room: active bookings exist.');
        } else {
            $conn->prepare('DELETE FROM rooms WHERE id=? AND homestay_id=?')->execute([$rid, $hid]);
            set_flash('success', 'Room deleted successfully.');
        }
        redirect(BASE_URL . 'owner/manage-rooms.php?homestay_id=' . $hid);
    }

    if ($action === 'toggle') {
        $rid = (int)($_POST['room_id'] ?? 0);
        $conn->prepare('UPDATE rooms SET is_active=IF(is_active=1,0,1) WHERE id=? AND homestay_id=?')->execute([$rid, $hid]);
        set_flash('success', 'Room status toggled.');
        redirect(BASE_URL . 'owner/manage-rooms.php?homestay_id=' . $hid);
    }
}

$rooms = [];
$calendar = [];
if ($homestayId) {
    $r = $conn->prepare('SELECT * FROM rooms WHERE homestay_id=? ORDER BY price_per_night');
    $r->execute([$homestayId]);
    $rooms = $r->fetchAll();

    $cal = $conn->prepare("SELECT bd.room_id, r.name AS room_name, b.check_in, b.check_out, b.status
        FROM bookings b JOIN booking_details bd ON bd.booking_id=b.id JOIN rooms r ON r.id=bd.room_id
        WHERE b.homestay_id=? AND b.status IN ('pending','confirmed') AND b.check_out >= CURDATE()
        ORDER BY b.check_in");
    $cal->execute([$homestayId]);
    $calendar = $cal->fetchAll();
}

$editRoom = null;
if (!empty($_GET['edit'])) {
    foreach ($rooms as $rr) {
        if ((int)$rr['id'] === (int)$_GET['edit']) $editRoom = $rr;
    }
}

$pageTitle = 'Manage Rooms';
$sidebarRole = 'owner';
$sidebarActive = 'rooms';
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php require __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="dashboard-main animate__animated animate__fadeIn">
        
        <!-- Header Bar -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <h1 class="h3 fw-bold text-dark m-0">Inventory & Rooms</h1>
                <p class="small text-muted mb-0">Create, edit, and monitor availability of rooms.</p>
            </div>
            <div>
                <?php if (!empty($homestays)): ?>
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="small text-muted fw-bold text-uppercase mb-0 text-nowrap">Select Property:</label>
                    <select name="homestay_id" class="form-select form-select-sm" onchange="this.form.submit()" style="max-width: 240px">
                        <?php foreach ($homestays as $hs): ?>
                        <option value="<?= (int)$hs['id'] ?>" <?= $homestayId==(int)$hs['id']?'selected':'' ?>><?= e($hs['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($homestays)): ?>
            <div class="text-center py-5">
                <div class="bg-light p-4 rounded-4 border max-width-auto d-inline-block px-5">
                    <i class="fas fa-bed fs-1 text-muted mb-3"></i>
                    <h5 class="fw-bold text-dark mb-1">No homestays available</h5>
                    <p class="text-muted small mb-3">You must list a homestay before adding rooms.</p>
                    <a href="<?= BASE_URL ?>owner/add-homestay.php" class="btn btn-teal btn-sm fw-bold">List Homestay</a>
                </div>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 small py-2 px-3 rounded-3 mb-4">
                    <i class="fas fa-circle-exclamation"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Add/Edit Room Column -->
                <div class="col-lg-5">
                    <div class="dash-card border p-4">
                        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-circle-plus text-teal me-2"></i><?= $editRoom ? 'Edit Room' : 'Add Room' ?></h5>
                        <form method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="<?= $editRoom ? 'edit' : 'add' ?>">
                            <input type="hidden" name="homestay_id" value="<?= $homestayId ?>">
                            <?php if ($editRoom): ?><input type="hidden" name="room_id" value="<?= (int)$editRoom['id'] ?>"><?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Room name</label>
                                <input name="name" class="form-control" placeholder="e.g. Deluxe Mountain View" value="<?= e($editRoom['name'] ?? '') ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Room type</label>
                                <select name="room_type" class="form-select fw-semibold">
                                    <?php foreach (['Private Room','Entire Place','Suite'] as $t): ?>
                                    <option <?= ($editRoom['room_type'] ?? '')===$t?'selected':'' ?>><?= $t ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="e.g. Double bed, view of valley..."><?= e($editRoom['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Max guests</label>
                                    <input type="number" name="max_guests" class="form-control text-center font-monospace" value="<?= (int)($editRoom['max_guests'] ?? 2) ?>" min="1">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Beds</label>
                                    <input type="number" name="beds" class="form-control text-center font-monospace" value="<?= (int)($editRoom['beds'] ?? 1) ?>" min="1">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Price / night</label>
                                    <input type="number" step="0.01" name="price_per_night" class="form-control text-center font-monospace fw-bold text-teal-deep" value="<?= e($editRoom['price_per_night'] ?? '') ?>" placeholder="INR" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-1">Cleaning fee</label>
                                <input type="number" step="0.01" name="cleaning_fee" class="form-control font-monospace" value="<?= e($editRoom['cleaning_fee'] ?? '0') ?>" placeholder="Cleaning fee amount">
                            </div>
                            
                            <button class="btn btn-teal w-100 py-2 fw-bold"><i class="fas fa-save me-1.5"></i><?= $editRoom ? 'Update Room' : 'Add Room' ?></button>
                            <?php if ($editRoom): ?>
                                <a href="?homestay_id=<?= $homestayId ?>" class="btn btn-light border w-100 py-2 mt-2 fw-semibold small text-muted">Cancel Edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Rooms List & Availability Column -->
                <div class="col-lg-7">
                    <!-- Rooms list -->
                    <div class="dash-card border p-4 mb-4">
                        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-bed text-teal me-2"></i>Rooms Inventory</h5>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($rooms as $r): ?>
                            <div class="room-card border rounded-3 p-3 bg-light bg-opacity-25 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <strong class="text-dark fs-6"><?= e($r['name']) ?></strong> 
                                        <?= status_badge($r['is_active'] ? 'active' : 'inactive') ?>
                                    </div>
                                    <div class="small text-muted">
                                        <span class="fw-bold text-teal-deep me-2"><?= money($r['price_per_night']) ?>/night</span>
                                        <span>&middot; Max <?= (int)$r['max_guests'] ?> guests</span>
                                        <span>&middot; <?= (int)$r['beds'] ?> bed<?= $r['beds'] > 1 ? 's' : '' ?></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <a href="?homestay_id=<?= $homestayId ?>&edit=<?= (int)$r['id'] ?>" class="btn btn-sm btn-light border fw-semibold">
                                        <i class="far fa-edit"></i>
                                    </a>
                                    <form method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="homestay_id" value="<?= $homestayId ?>">
                                        <input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn btn-sm btn-outline-warning" title="Toggle active status">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this room?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="homestay_id" value="<?= $homestayId ?>">
                                        <input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Delete room">
                                            <i class="far fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($rooms)): ?>
                                <p class="text-muted small mb-0">No rooms added to this property yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Availability Dates -->
                    <div class="dash-card border p-4">
                        <h5 class="fw-bold text-dark mb-3"><i class="far fa-calendar-check text-teal me-2"></i>Upcoming Bookings Calendar</h5>
                        <div class="d-flex flex-column gap-2">
                            <?php if (empty($calendar)): ?>
                                <p class="text-muted small mb-0">No upcoming blocked check-in dates.</p>
                            <?php else: foreach ($calendar as $c): ?>
                                <div class="d-flex align-items-center justify-content-between p-2.5 rounded border bg-light bg-opacity-20 small">
                                    <div>
                                        <strong class="text-dark"><?= e($c['room_name']) ?></strong>
                                        <span class="d-block text-muted" style="font-size: 0.75rem"><i class="far fa-calendar me-1"></i><?= format_date($c['check_in']) ?> to <?= format_date($c['check_out']) ?></span>
                                    </div>
                                    <?= status_badge($c['status']) ?>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
