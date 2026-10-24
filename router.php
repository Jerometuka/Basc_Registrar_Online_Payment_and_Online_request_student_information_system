<?php
$page = isset($_GET['page']) ? $_GET['page'] : 'signin.php';

if (file_exists($page)) {
    require $page;
} else {
    http_response_code(404);
    echo "404 - Page not found.";
}
?>
