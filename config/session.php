<script>
document.addEventListener("DOMContentLoaded", function () {

            document.querySelectorAll(".sn-toast").forEach(function (toast) {

                setTimeout(function () {

                    bootstrap.Alert.getOrCreateInstance(toast).close();

                }, 5000);

            });

        });
</script>
<style>/* Flash Notification */
 .sn-toast {
            position: relative;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            padding: 16px 18px;
            margin-bottom: 18px;
            animation: slideDown .35s ease;
        }

        .sn-toast-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #fff;
        }

        .alert-success .sn-toast-icon {
            background: #198754;
        }

        .alert-danger .sn-toast-icon {
            background: #dc3545;
        }

        .alert-success {
            background: #ecfdf5;
            color: #14532d;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
        }

        .btn-close {
            opacity: .7;
        }

        .btn-close:hover {
            opacity: 1;
        }

        .sn-progress {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 4px;
            width: 100%;
            animation: progressBar 5s linear forwards;
        }

        .alert-success .sn-progress {
            background: #198754;
        }

        .alert-danger .sn-progress {
            background: #dc3545;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes progressBar {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }
</style>
<?php
// Start session safely
require_once __DIR__ . '/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

function check_csrf()
{
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid request. Please go back and try again.');
    }
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function is_owner()
{
    return is_logged_in() && $_SESSION['role'] === 'owner';
}

function is_user()
{
    return is_logged_in() && $_SESSION['role'] === 'user';
}

function require_login($role = '')
{
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please login first.';
        header('Location: ' . BASE_URL . 'authentication/login.php');
        exit;
    }
    if ($role !== '' && $_SESSION['role'] !== $role) {
        $_SESSION['flash_error'] = 'Access denied.';
        header('Location: ' . BASE_URL);
        exit;
    }
}

function set_flash($type, $message)
{
    $_SESSION['flash_' . $type] = $message;
}

function show_flash()
{
    $alerts = [
        'success' => [
            'class' => 'success',
            'icon'  => 'fa-circle-check',
            'title' => 'Success'
        ],
        'error' => [
            'class' => 'danger',
            'icon'  => 'fa-circle-xmark',
            'title' => 'Error'
        ]
    ];

    $html = '';

    foreach ($alerts as $type => $alert) {

        $key = 'flash_' . $type;

        if (!empty($_SESSION[$key])) {

            $message = htmlspecialchars($_SESSION[$key], ENT_QUOTES, 'UTF-8');

            // $html .= "
            // <div class='sn-toast alert alert-{$alert['class']} alert-dismissible fade show shadow-sm' role='alert'>

            //     <div class='d-flex align-items-center'>

            //         <div class='sn-toast-icon'>
            //             <i class='fa-solid {$alert['icon']}'></i>
            //         </div>

            //         <div class='ms-3 flex-grow-1'>
            //             <div class='fw-bold'>{$alert['title']}</div>
            //             <div>{$message}</div>
            //         </div>

            //         <button type='button'
            //                 class='btn-close'
            //                 data-bs-dismiss='alert'
            //                 aria-label='Close'></button>

            //     </div>

            //     <div class='sn-progress'></div>

            // </div>";
            $html.="
    <div class='sn-toast alert alert-success alert-dismissible fade show shadow-sm' role='alert'>

        <div class='d-flex align-items-center'>

            <div class='sn-toast-icon'>
                <i class='fa-solid {$alert['icon']}'></i>
            </div>

            <div class='ms-3 flex-grow-1'>
                <div class='fw-bold'>{$alert['title']}</div>
                <div>{$message}</div>
            </div>

            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>

        </div>

        <div class='sn-progress'></div>

    </div>";

            unset($_SESSION[$key]);
        }
    }

    return $html;
}

