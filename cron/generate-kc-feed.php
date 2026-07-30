<?php
/**
 * Generate RSS 2.0 feed for Knowledge Center articles.
 * Output: xsite/knowledge-center/feed.xml
 *
 * Google, Bing, and feed readers pick up new articles via this feed,
 * typically faster than waiting for a full sitemap crawl.
 *
 * Cron: run nightly after sitemap regen.
 */

$_SERVER['HTTP_HOST']     = 'www.bombayengg.net';
$_SERVER['REQUEST_URI']   = '/';
$_SERVER['SERVER_PORT']   = '443';
$_SERVER['HTTPS']         = 'on';
$_SERVER['DOCUMENT_ROOT'] = '/home/bombayengg/public_html';

require_once('/home/bombayengg/public_html/config.inc.php');
require_once('/home/bombayengg/public_html/core/core.inc.php');

$BASE   = 'https://www.bombayengg.net';
$FEED_URL = $BASE . '/knowledge-center/feed.xml';
// Note: cannot use xsite/knowledge-center/feed.xml because that directory would
// conflict with the CMS route for /knowledge-center/. Instead write to xsite root
// and let .htaccess rewrite /knowledge-center/feed.xml → kc-feed.xml.
$OUT    = '/home/bombayengg/public_html/xsite/kc-feed.xml';

$DB->vals = [1];
$DB->types = "i";
$DB->sql = "SELECT knowledgeCenterID, knowledgeCenterTitle, seoUri, synopsis,
                   knowledgeCenterImage, datePublish
            FROM mx_knowledge_center
            WHERE status=?
            ORDER BY datePublish DESC, knowledgeCenterID DESC
            LIMIT 50";
$articles = $DB->dbRows();

$lastBuildDate = date('r'); // RFC 2822

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";
$xml .= '<channel>' . "\n";
$xml .= '  <title>Bombay Engineering Syndicate — Knowledge Center</title>' . "\n";
$xml .= '  <link>' . $BASE . '/knowledge-center/</link>' . "\n";
$xml .= '  <atom:link href="' . $FEED_URL . '" rel="self" type="application/rss+xml" />' . "\n";
$xml .= '  <description>Technical articles on industrial motors, pumps, energy efficiency, and selection guides from Bombay Engineering Syndicate — authorized Crompton distributor since 1957.</description>' . "\n";
$xml .= '  <language>en-in</language>' . "\n";
$xml .= '  <copyright>Copyright ' . date('Y') . ', Bombay Engineering Syndicate</copyright>' . "\n";
$xml .= '  <lastBuildDate>' . $lastBuildDate . '</lastBuildDate>' . "\n";
$xml .= '  <generator>Bombay Engineering Syndicate KC Feed</generator>' . "\n";
$xml .= '  <image>' . "\n";
$xml .= '    <url>' . $BASE . '/images/logo.png</url>' . "\n";
$xml .= '    <title>Bombay Engineering Syndicate</title>' . "\n";
$xml .= '    <link>' . $BASE . '/</link>' . "\n";
$xml .= '  </image>' . "\n\n";

foreach ($articles as $a) {
    $articleUrl = $BASE . '/knowledge-center/' . $a['seoUri'] . '/';
    $pubDate = date('r', strtotime($a['datePublish']));
    $title = htmlspecialchars($a['knowledgeCenterTitle'], ENT_XML1, 'UTF-8');
    $synopsis = htmlspecialchars(trim($a['synopsis'] ?? ''), ENT_XML1, 'UTF-8');
    $guid = htmlspecialchars($articleUrl, ENT_XML1, 'UTF-8');

    $imageUrl = '';
    $imageLength = 0;
    $imageMime = 'image/webp';
    if (!empty($a['knowledgeCenterImage'])) {
        $imagePath = '/home/bombayengg/public_html/uploads/knowledge-center/' . $a['knowledgeCenterImage'];
        if (file_exists($imagePath)) {
            $imageUrl = $BASE . '/uploads/knowledge-center/' . $a['knowledgeCenterImage'];
            $imageLength = filesize($imagePath);
            $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            $mimeMap = ['webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
            $imageMime = $mimeMap[$ext] ?? 'image/webp';
        }
    }

    $xml .= '  <item>' . "\n";
    $xml .= '    <title>' . $title . '</title>' . "\n";
    $xml .= '    <link>' . $articleUrl . '</link>' . "\n";
    $xml .= '    <guid isPermaLink="true">' . $guid . '</guid>' . "\n";
    $xml .= '    <pubDate>' . $pubDate . '</pubDate>' . "\n";
    $xml .= '    <dc:creator>Bombay Engineering Syndicate</dc:creator>' . "\n";
    $xml .= '    <description>' . $synopsis . '</description>' . "\n";
    if ($imageUrl && $imageLength > 0) {
        $xml .= '    <enclosure url="' . $imageUrl . '" length="' . $imageLength . '" type="' . $imageMime . '" />' . "\n";
    }
    $xml .= '    <category>Industrial Motors</category>' . "\n";
    $xml .= '    <category>Pumps</category>' . "\n";
    $xml .= '  </item>' . "\n\n";
}

$xml .= '</channel>' . "\n";
$xml .= '</rss>' . "\n";

file_put_contents($OUT, $xml);

echo "RSS feed written: " . strlen($xml) . " bytes, " . count($articles) . " articles\n";
echo "URL: $FEED_URL\n";
