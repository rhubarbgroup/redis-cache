<?php
/**
 * Compatiblity file to avoid WSOD/recovery-mode updating from 2.0.0 as
 * `require_once` was defined by a condition in this specific version.
 *
 * Simply returning false will promt the admin to update his `object-cache.php`
 */

return false;
