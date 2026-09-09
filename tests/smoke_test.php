<?php
/**
 * ZeroLMS Automated Sanity & Smoke Test Suite
 * Designed for GitHub Actions CI/CD pipeline
 */

echo "========================================\n";
echo "   ZeroLMS Automated CI/CD Test Suite  \n";
echo "========================================\n\n";

$errors = 0;

function assert_test($description, $condition) {
    global $errors;
    if ($condition) {
        echo " [PASS] $description\n";
    } else {
        echo " [FAIL] $description\n";
        $errors++;
    }
}

// 1. Test icons.php
echo "--- 1. Testing SVG Icon Component ---\n";
$icons_path = __DIR__ . '/../icons.php';
assert_test("icons.php file exists", file_exists($icons_path));

if (file_exists($icons_path)) {
    require_once $icons_path;
    assert_test("icon() function is defined", function_exists('icon'));
    
    $required_icons = ['phone', 'envelope', 'clock', 'globe', 'bars', 'xmark', 'house', 'users', 'newspaper'];
    foreach ($required_icons as $ico) {
        $svg = icon($ico);
        assert_test("icon('$ico') renders valid SVG", !empty($svg) && str_contains($svg, '<svg') && str_contains($svg, '</svg>'));
    }
}

// 2. Test Manifest JSON Validity
echo "\n--- 2. Testing PWA & Manifest Configuration ---\n";
$manifest_path = __DIR__ . '/../manifest.json';
assert_test("manifest.json file exists", file_exists($manifest_path));
if (file_exists($manifest_path)) {
    $manifest_data = json_decode(file_get_contents($manifest_path), true);
    assert_test("manifest.json is valid JSON", json_last_error() === JSON_ERROR_NONE && is_array($manifest_data));
    assert_test("manifest.json contains 'name' attribute", !empty($manifest_data['name']));
}

// 3. Test Critical Assets Existence
echo "\n--- 3. Testing Critical System Files ---\n";
$critical_files = [
    'index.php' => 'Landing page core engine',
    'info.html' => 'Architecture and system specification',
    'docker-compose.yml' => 'Container orchestration spec',
    'Dockerfile' => 'PHP container build instructions',
    'css/landing.min.css' => 'Precompiled critical CSS',
    'js/swiper-bundle.min.js' => 'Localized Swiper JS bundle',
    'js/aos.js' => 'Localized AOS JS bundle',
    'images/logo.webp' => 'Optimized WebP Logo',
    'images/slide1-mobile.webp' => 'Mobile hero image LCP',
];

foreach ($critical_files as $file => $desc) {
    $path = __DIR__ . '/../' . $file;
    assert_test("$desc ($file)", file_exists($path) && filesize($path) > 0);
}

// 4. Test SQL Schema Completeness
echo "\n--- 4. Testing Database Schema ---\n";
$sql_path = __DIR__ . '/../backupdatabase.sql';
assert_test("backupdatabase.sql exists", file_exists($sql_path));
if (file_exists($sql_path)) {
    $sql_content = file_get_contents($sql_path);
    assert_test("Schema contains 'users' table definition", str_contains($sql_content, 'CREATE TABLE `users`') || str_contains($sql_content, 'CREATE TABLE users'));
    assert_test("Schema contains 'posts' table definition", str_contains($sql_content, 'CREATE TABLE `posts`') || str_contains($sql_content, 'CREATE TABLE posts'));
    assert_test("Schema contains 'teachers' table definition", str_contains($sql_content, 'CREATE TABLE `teachers`') || str_contains($sql_content, 'CREATE TABLE teachers'));
}

echo "\n========================================\n";
if ($errors === 0) {
    echo " ALL TESTS PASSED SUCCESSFULLY! (0 errors)\n";
    echo "========================================\n";
    exit(0);
} else {
    echo " TEST SUITE FAILED WITH $errors ERROR(S)!\n";
    echo "========================================\n";
    exit(1);
}
