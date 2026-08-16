<?php
// Common helper functions - simple style
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

function e($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function money($amount)
{
    return '₹' . number_format((float)$amount, 2);
}

function format_date($date)
{
    if (!$date) return '';
    return date('d M Y', strtotime($date));
}

function asset($path)
{
    return BASE_URL . 'assets/' . ltrim($path, '/');
}

function profile_img($filename)
{
    if ($filename && file_exists(UPLOAD_PROFILES . $filename)) {
        return BASE_URL . 'assets/uploads/profiles/' . $filename;
    }
    return asset('images/default-avatar.svg');
}

function homestay_img($filename)
{
    if ($filename && file_exists(UPLOAD_HOMESTAYS . $filename)) {
        return BASE_URL . 'assets/uploads/homestays/' . $filename;
    }
    return asset('images/placeholder-homestay.svg');
}

// Nice photo for cards/gallery when owner has not uploaded yet
function display_image($h)
{
    if (!empty($h['cover_image']) && file_exists(UPLOAD_HOMESTAYS . $h['cover_image'])) {
        return BASE_URL . 'assets/uploads/homestays/' . $h['cover_image'];
    }
    $city = strtolower(trim($h['city'] ?? ''));
    $photos = [
        'khechuperi' => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=900&q=80',
        'pelling'    => 'https://images.unsplash.com/photo-1589308078059-be1415eab4c3?w=900&q=80',
        'yuksom'     => 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=900&q=80',
        'geyzing'    => 'https://images.unsplash.com/photo-1605649487212-47bdab064df7?w=900&q=80',
        'sikkim'     => 'https://images.unsplash.com/photo-1626082927389-6cd097cdc6ec?w=900&q=80',
        'manali'     => 'https://images.unsplash.com/photo-1626621341517-bbf3d9990a23?w=900&q=80',
        'goa'        => 'https://images.unsplash.com/photo-1512343879784-a960cd418056?w=900&q=80',
    ];
    if (isset($photos[$city])) {
        return $photos[$city];
    }
    // Stable random-looking image per id
    $id = (int)($h['id'] ?? 1);
    $pool = [
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=900&q=80',
        'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=900&q=80',
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=900&q=80',
        'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=900&q=80',
        'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=900&q=80',
        'https://images.unsplash.com/photo-1499793983690-e8b21befc6c4?w=900&q=80',
    ];
    return $pool[$id % count($pool)];
}

function first_name($fullName)
{
    $fullName = trim($fullName ?? '');
    if ($fullName === '') return 'User';
    $parts = explode(' ', $fullName);
    return $parts[0];
}

// Simple image upload
function upload_image($file, $folder)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        return false;
    }

    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newName = 'img_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $path = rtrim($folder, '/\\') . DIRECTORY_SEPARATOR . $newName;

    if (move_uploaded_file($file['tmp_name'], $path)) {
        return $newName;
    }
    return false;
}

function nights_between($checkIn, $checkOut)
{
    $d1 = new DateTime($checkIn);
    $d2 = new DateTime($checkOut);
    $n = (int)$d1->diff($d2)->days;
    return $n < 1 ? 1 : $n;
}

function booking_ref()
{
    return 'SN' . strtoupper(substr(md5(uniqid()), 0, 8));
}

function stars($rating)
{
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-warning"></i>';
        }
    }
    $html .= ' <small>' . number_format($rating, 1) . '</small>';
    return $html;
}

function status_badge($status)
{
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'secondary',
        'completed' => 'info',
        'paid' => 'success',
        'active' => 'success',
        'inactive' => 'secondary',
    ];
    $c = $colors[$status] ?? 'secondary';
    return '<span class="badge bg-' . $c . '">' . e(ucfirst($status)) . '</span>';
}

// Get owner id from logged in user
function get_owner_id()
{
    global $conn;
    if (!is_owner()) return 0;
    $stmt = $conn->prepare('SELECT id FROM owners WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : 0;
}

// Check if room is free for dates
function is_room_available($roomId, $checkIn, $checkOut)
{
    global $conn;
    $sql = "SELECT COUNT(*) FROM bookings b
            JOIN booking_details bd ON b.id = bd.booking_id
            WHERE bd.room_id = ?
            AND b.status IN ('pending', 'confirmed')
            AND b.check_in < ? AND b.check_out > ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$roomId, $checkOut, $checkIn]);
    return (int)$stmt->fetchColumn() === 0;
}

// Create a notification
function add_notification($userId, $title, $message, $link = null)
{
    global $conn;
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $link]);
}

function unread_count()
{
    global $conn;
    if (!is_logged_in()) return 0;
    $stmt = $conn->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

// Simple homestay card HTML
function homestay_card($h)
{
    $img = display_image($h);
    $price = $h['min_price'] ?? 0;
    $rating = (float)($h['avg_rating'] ?? 0);
    $id = (int)($h['id'] ?? 0);

    $html = '<div class="homestay-card" data-aos="fade-up">';
    $html .= '<div class="card-img-wrap">';
    $html .= '<a href="' . BASE_URL . 'pages/homestay-details.php?id=' . $id . '">';
    $html .= '<img src="' . e($img) . '" alt="' . e($h['title']) . '" class="card-img" loading="lazy">';
    $html .= '</a>';
    $html .= '<span class="card-badge">' . e($h['property_type'] ?? 'Homestay') . '</span>';
    $html .= '</div>';
    $html .= '<div class="card-body-custom">';
    $html .= '<div class="card-location"><i class="fas fa-map-marker-alt"></i> ' . e($h['city']) . ', ' . e($h['state']) . '</div>';
    $html .= '<h3 class="card-title"><a href="' . BASE_URL . 'pages/homestay-details.php?id=' . $id . '">' . e($h['title']) . '</a></h3>';
    $html .= '<div class="card-meta">' . stars($rating) . '</div>';
    $html .= '<div class="card-footer-custom">';
    $html .= '<div class="card-price">' . money((float)$price) . ' <small>/ night</small></div>';
    $html .= '<a href="' . BASE_URL . 'pages/homestay-details.php?id=' . $id . '" class="btn btn-sm btn-primary">View</a>';
    $html .= '</div></div></div>';
    return $html;
}

// Count how many owners exist (system allows only 1)
function owner_count()
{
    global $conn;
    try {
        return (int)$conn->query('SELECT COUNT(*) FROM owners')->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function make_slug($text)
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ? $text : 'homestay-' . time();
}

function time_elapsed($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $w = floor($diff->d / 7);
    $d = $diff->d - ($w * 7);

    $string = [
        'y' => ['year', $diff->y],
        'm' => ['month', $diff->m],
        'w' => ['week', $w],
        'd' => ['day', $d],
        'h' => ['hour', $diff->h],
        'i' => ['minute', $diff->i],
        's' => ['second', $diff->s],
    ];

    $result = [];
    foreach ($string as $k => $info) {
        $val = $info[1];
        $label = $info[0];
        if ($val > 0) {
            $result[$k] = $val . ' ' . $label . ($val > 1 ? 's' : '');
        }
    }

    if (!$full) $result = array_slice($result, 0, 1);
    return $result ? implode(', ', $result) . ' ago' : 'just now';
}
