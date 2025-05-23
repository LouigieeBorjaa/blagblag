<?php

namespace Aries\Dbmodel\Includes;

class Session {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            
            // Start the session
            session_start();
        }
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public function remove($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public function destroy() {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy the session
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public function getUserName() {
        return $_SESSION['user_name'] ?? null;
    }

    public function regenerate() {
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } else {
            $interval = 1800; // 30 minutes
            if (time() - $_SESSION['last_regeneration'] >= $interval) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
} 