<?php
/**
 * Sitemap Generator - Creates sitemap.xml file on server
 * Uses all parameters from param.txt randomly
 */

// Get base URL
$basePath = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
$basePath .= "://" . $_SERVER['HTTP_HOST'];
$baseUrl = $basePath . dirname($_SERVER['REQUEST_URI']);

// Function to sanitize keywords for URL
function sanitizeKeyword($keyword) {
    $keyword = trim($keyword);
    return urlencode($keyword);
}

// Function to get current timestamp in W3C format
function getCurrentTimestamp() {
    return date('c');
}

// Read valid parameters from param.txt
$paramFile = __DIR__ . '/param.txt';
if (!file_exists($paramFile)) {
    die('Error: File param.txt tidak ditemukan!');
}

$content = file_get_contents($paramFile);
if ($content === false) {
    die('Error: Tidak dapat membaca file param.txt!');
}

$validKeys = explode(' ', trim($content));
$cleanKeys = array();
foreach ($validKeys as $key) {
    $trimmedKey = trim($key);
    if ($trimmedKey !== '') {
        $cleanKeys[] = $trimmedKey;
    }
}

if (count($cleanKeys) === 0) {
    die('Error: File param.txt kosong atau tidak berisi parameter yang valid!');
}

// Read Thai keywords from thaikeyword.txt
$keywordFile = __DIR__ . '/thaikeyword.txt';
if (!file_exists($keywordFile)) {
    die('Error: File thaikeyword.txt tidak ditemukan!');
}

$content = file_get_contents($keywordFile);
if ($content === false) {
    die('Error: Tidak dapat membaca file thaikeyword.txt!');
}

// Handle different line endings
$thaiKeywords = preg_split('/\r\n|\r|\n/', trim($content));
$cleanKeywords = array();
foreach ($thaiKeywords as $keyword) {
    $trimmedKeyword = trim($keyword);
    if ($trimmedKeyword !== '') {
        $cleanKeywords[] = $trimmedKeyword;
    }
}

if (count($cleanKeywords) === 0) {
    die('Error: File thaikeyword.txt kosong atau tidak berisi keyword yang valid!');
}

// Start building XML content
$xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xmlContent .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Generate URLs for each combination of valid key + Thai keyword
$urlCount = 0;
$maxUrls = 50000; // Sitemap limit

// Shuffle parameters for randomization
$shuffledKeys = $cleanKeys;
shuffle($shuffledKeys);

foreach ($cleanKeywords as $keyword) {
    if ($urlCount >= $maxUrls) break;
    
    // Get random parameter for this keyword
    $randomKey = $shuffledKeys[array_rand($shuffledKeys)];
    
    // Create URL with parameter
    $encodedKeyword = sanitizeKeyword($keyword);
    $pageUrl = $baseUrl . "/?" . $randomKey . "=" . $encodedKeyword;
    
    $xmlContent .= "  <url>\n";
    $xmlContent .= "    <loc>" . htmlspecialchars($pageUrl) . "</loc>\n";
    $xmlContent .= "    <lastmod>" . getCurrentTimestamp() . "</lastmod>\n";
    $xmlContent .= "    <changefreq>daily</changefreq>\n";
    $xmlContent .= "    <priority>1.0</priority>\n";
    $xmlContent .= "  </url>\n";
    
    $urlCount++;
}

// Close XML
$xmlContent .= "</urlset>\n";

// Write sitemap.xml file to server
$sitemapFile = __DIR__ . '/sitemap.xml';
$result = file_put_contents($sitemapFile, $xmlContent);

if ($result === false) {
    die('Error: Tidak dapat menulis file sitemap.xml!');
}

// Set content type and show success message
header('Content-Type: text/plain; charset=utf-8');
echo "SUCCESS: Sitemap berhasil dibuat!\n\n";
echo "Detail:\n";
echo "- File: " . $sitemapFile . "\n";
echo "- Total URLs: " . $urlCount . "\n";
echo "- File size: " . number_format(filesize($sitemapFile)) . " bytes\n";
echo "- Parameters used: " . count($cleanKeys) . " parameters\n";
echo "- Keywords used: " . count($cleanKeywords) . " keywords\n\n";
echo "Sitemap XML dapat diakses di:\n";
echo $baseUrl . "/sitemap.xml\n\n";

// Show sample URLs
echo "Sample URLs (first 5):\n";
$lines = explode("\n", $xmlContent);
$counter = 0;
foreach ($lines as $line) {
    if (strpos($line, '<loc>') !== false && $counter < 5) {
        $url = trim(str_replace(array('<loc>', '</loc>'), '', $line));
        echo ($counter + 1) . ". " . $url . "\n";
        $counter++;
    }
}

echo "\nSitemap generation completed successfully!";
?>