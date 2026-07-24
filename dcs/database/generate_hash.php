<?php
/**
 * One-off helper: generates a password_hash() value compatible with
 * login.php's password_verify() call, for use in database/setup.sql.
 *
 * Usage:
 *   php generate_hash.php admin123
 *   (or just: php generate_hash.php   -> defaults to "admin123")
 */
$password = $argv[1] ?? 'admin123';
echo password_hash($password, PASSWORD_DEFAULT) . "\n";
