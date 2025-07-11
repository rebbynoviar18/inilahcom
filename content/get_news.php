<?php
header('Content-Type: application/json');

function fetchRssFeed($url, $limit = 10) {
    try {
        $feed = simplexml_load_file($url);
        if (!$feed) {
            return [];
        }
        
        $items = [];
        $count = 0;
        
        // Untuk Atom feed (inilah.com menggunakan format Atom)
        foreach ($feed->entry as $item) {
            if ($count >= $limit) break;
            
            // Tambahkan penanganan untuk memastikan link valid
            $link = isset($item->link['href']) ? (string)$item->link['href'] : '';
            if (empty($link) && isset($item->link)) {
                $link = (string)$item->link;
            }
            
            $items[] = [
                'title' => (string)$item->title,
                'link' => $link,
                'date' => (string)$item->published
            ];
            $count++;
        }
        
        return $items;
    } catch (Exception $e) {
        return [];
    }
}

// Ambil berita dari RSS feed
$newsItems = fetchRssFeed('https://www.inilah.com/atom.xml', 10);

echo json_encode([
    'success' => true,
    'items' => $newsItems
]);
