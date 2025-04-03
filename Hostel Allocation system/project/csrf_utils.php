<?php
/**
 * Verify the CSRF token from the form submission against the stored session token
 * @param string $token The token from the form submission
 * @return bool Returns true if the token is valid, false otherwise
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    if (empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
