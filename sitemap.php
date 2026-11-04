<?php
    // XML sitemap for hellotechnology.
    // Served directly (it's a real file, so Runway's .htaccess rewrite skips it).
    // Also available at /sitemap.xml via a rewrite rule in .htaccess.
    header('Content-Type: application/xml; charset=utf-8');
    include(__DIR__ . '/admin/runtime.php');

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    // Pages from the Runway page tree (flat list, one <url> each).
    // Pages set to "hide from navigation" are excluded.
    perch_pages_navigation([
        'template'        => 'sitemap.html',
        'flat'            => true,
        'hide-extensions' => true,
    ]);

    // All published blog posts, newest first.
    if (function_exists('perch_blog_custom')) {
        perch_blog_custom([
            'template'   => 'sitemap_post.html',
            'count'      => 10000,
            'sort'       => 'postDateTime',
            'sort-order' => 'DESC',
        ]);
    }

    echo '</urlset>' . "\n";
