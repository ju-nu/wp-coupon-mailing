<?php
declare(strict_types=1);

// Clear any previous output
if (headers_sent()) {
    // Optional: handle the case if headers are already sent
    exit('Cannot redirect; headers already sent!');
}

// Best practice: disable caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Set the 301 status code and the Location header
http_response_code(301);
header('Location: https://vorteilplus.de');

// Stop script execution to ensure the redirect happens immediately
exit;
