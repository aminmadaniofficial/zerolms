<?php
/**
 * ZeroLMS Dynamic XML Sitemap Generator
 * Generates standards-compliant sitemap.xml for Google Search Console & search engine crawlers
 */

require_once __DIR__ . '/db.php';

$domain = "https://bahonarkaraj.ir"; // Canonical production domain

$urls = [
    [
        'loc' => $domain . '/',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '1.0'
    ],
    [
        'loc' => $domain . '/info.html',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.9'
    ],
    [
        'loc' => $domain . '/blog/',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '0.9'
    ],
    [
        'loc' => $domain . '/teachers/',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.9'
    ],
    [
        'loc' => $domain . '/gallery/',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'weekly',
        'priority' => '0.8'
    ],
    [
        'loc' => $domain . '/login/',
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'monthly',
        'priority' => '0.6'
    ],
];

// Query all blog posts
if ($pdo) {
    try {
        $stmtP = $pdo->query("SELECT id, created_at FROM posts ORDER BY id DESC");
        while ($row = $stmtP->fetch(PDO::FETCH_ASSOC)) {
            $lastmod = !empty($row['created_at']) ? date('Y-m-d', strtotime($row['created_at'])) : date('Y-m-d');
            $urls[] = [
                'loc' => $domain . '/blog/post/index.php?id=' . (int)$row['id'],
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => '0.8'
            ];
        }

        // Query all teachers
        $stmtT = $pdo->query("SELECT u.id FROM teachers t JOIN users u ON t.user_id = u.id ORDER BY u.id ASC");
        while ($row = $stmtT->fetch(PDO::FETCH_ASSOC)) {
            $urls[] = [
                'loc' => $domain . '/teachers/profile.php?id=' . (int)$row['id'],
                'lastmod' => date('Y-m-d'),
                'changefreq' => 'monthly',
                'priority' => '0.7'
            ];
        }
    } catch (PDOException $e) {
        error_log("Sitemap DB Query error: " . $e->getMessage());
    }
}

// Build XML structure
$xml = new DOMDocument('1.0', 'UTF-8');
$xml->formatOutput = true;

$urlset = $xml->createElement('urlset');
$urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
$urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
$urlset->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');

foreach ($urls as $item) {
    $urlElem = $xml->createElement('url');
    
    $locElem = $xml->createElement('loc', htmlspecialchars($item['loc'], ENT_XML1, 'UTF-8'));
    $urlElem->appendChild($locElem);
    
    if (!empty($item['lastmod'])) {
        $modElem = $xml->createElement('lastmod', $item['lastmod']);
        $urlElem->appendChild($modElem);
    }
    
    if (!empty($item['changefreq'])) {
        $freqElem = $xml->createElement('changefreq', $item['changefreq']);
        $urlElem->appendChild($freqElem);
    }
    
    if (!empty($item['priority'])) {
        $prioElem = $xml->createElement('priority', $item['priority']);
        $urlElem->appendChild($prioElem);
    }
    
    $urlset->appendChild($urlElem);
}

$xml->appendChild($urlset);

$output_file = __DIR__ . '/sitemap.xml';
$xml->save($output_file);
echo "Sitemap generated successfully with " . count($urls) . " URLs at: $output_file\n";
