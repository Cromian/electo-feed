<?php
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: text/xml');

// Function to check status.
function getCode($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, true);    // we want headers
    curl_setopt($ch, CURLOPT_NOBODY, true);    // we don't need body
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_TIMEOUT,10);
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode == 403) {
        $status = 'no';
    } else {
        $status = 'yes';
    }
    return $status;
}

// Sanitize Data
function sanitize($var) {
    $data = htmlspecialchars(html_entity_decode($var, ENT_QUOTES, 'UTF-8'),ENT_QUOTES, 'UTF-8');
    return $data;
}

// Run feed parse
$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
$feed_src = 'https://news.google.com/news/rss/search/section/q/us+elections?ned=us&gl=US&hl=en';

$news = simplexml_load_file($feed_src);
$feeds = array();
$i = 0;

foreach ($news->channel->item as $item) 
{
    $result = getCode((string) $item->title);
    if ($result == 'yes') {

        $title = sanitize((string) $item->title);
        $link = sanitize((string) $item->link);
        $date = sanitize((string) $item->pubDate);
        $meta = get_meta_tags($link);
        $description = sanitize($meta['description']);

        if ($description != null) {
            $feeds[$i]['title'] = $title;
            $feeds[$i]['link'] = $link;
            $feeds[$i]['description'] = $description;
            $feeds[$i]['date'] = $date;
        }
    
    }
    $i++;
}
ob_flush();
flush();

// Output data
echo '<rss version="2.0"><channel>';
foreach ($feeds as $article) {
    echo '<item>';
        echo '<title>'. $article['title'] .'</title>';
        echo '<link>'. $article['link'] .'</link>';
        echo '<description>'. $article['description'] .'</description>';
    echo '</item>';
}
echo '</channel></rss>';

?>