<?php
/* Structured data (JSON-LD) builder — functions only, no output on include.

   One @graph per page, built server-side so crawlers that don't run JavaScript
   (most AI crawlers) still see it. Every entity has a stable @id so nodes can
   reference each other:

     https://hellotechnology.co.uk/#organization   Hello Technology Ltd
     https://hellotechnology.co.uk/#jack           Jack Barber (founder / author)
     https://hellotechnology.co.uk/#website        the site
     <page url>#webpage, #breadcrumb, #faq, #service, #article …

   Usage from a page template:
     ht_schema_page([...])                  describe the current page (all optional)
     ht_schema_add([...])                   add any extra node (Service, BlogPosting …)
     ht_schema_faq_from_html($html)         harvest .faq__item Q&As from rendered markup
   global.footer.php calls ht_schema_render() to print the graph. */

if (!defined('HT_SITE')) define('HT_SITE', 'https://hellotechnology.co.uk');

if (!function_exists('ht_schema_render')) {

    /** Flatten a Perch field value (string, or array with raw/processed) to a string. */
    function ht_val($v) {
        if (is_array($v)) {
            foreach (['processed', 'raw', '_default', 'text'] as $k) {
                if (isset($v[$k]) && is_scalar($v[$k])) return (string) $v[$k];
            }
            foreach ($v as $x) { if (is_scalar($x)) return (string) $x; }
            return '';
        }
        return is_scalar($v) ? (string) $v : '';
    }

    /** HTML -> single-line plain text. */
    function ht_text($html) {
        $html = preg_replace('/<\/?(p|br|li|ul|ol|div|h[1-6])\b[^>]*>/i', ' ', (string) $html);
        $t = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $t));
    }

    function ht_schema_state() {
        if (!isset($GLOBALS['ht_schema'])) {
            $GLOBALS['ht_schema'] = ['nodes' => [], 'page' => [], 'faq' => []];
        }
        return $GLOBALS['ht_schema'];
    }

    /** Describe the current page. Keys: type, name, description, image, datePublished,
        dateModified, crumb (label for the last breadcrumb). */
    function ht_schema_page(array $props) {
        ht_schema_state();
        $GLOBALS['ht_schema']['page'] = array_merge($GLOBALS['ht_schema']['page'], $props);
    }

    /** Add a node to the graph (nodes with the same @id replace each other). */
    function ht_schema_add(array $node) {
        ht_schema_state();
        if (isset($node['@id'])) $GLOBALS['ht_schema']['nodes'][$node['@id']] = $node;
        else $GLOBALS['ht_schema']['nodes'][] = $node;
    }

    function ht_dom($html) {
        $d = new DOMDocument();
        libxml_use_internal_errors(true);
        $d->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        return $d;
    }

    /** Text of the first <tag> (e.g. h1) in rendered HTML, or ''. */
    function ht_first_tag_text($html, $tag) {
        if (!class_exists('DOMDocument') || stripos($html, '<' . $tag) === false) return '';
        $n = ht_dom($html)->getElementsByTagName($tag)->item(0);
        return $n ? ht_text($n->textContent) : '';
    }

    /** Collect Q&As from rendered .faq__item markup (class names from the FAQ blocks). */
    function ht_schema_faq_from_html($html) {
        if (!class_exists('DOMDocument') || stripos($html, 'faq__item') === false) return;
        ht_schema_state();
        $d = ht_dom($html);
        $x = new DOMXPath($d);
        $has = function ($c) { return "contains(concat(' ', normalize-space(@class), ' '), ' $c ')"; };
        foreach ($x->query('//*[' . $has('faq__item') . ']') as $item) {
            $q = $x->query('.//*[' . $has('faq__q') . ']', $item)->item(0);
            $a = $x->query('.//*[' . $has('faq__a') . ']', $item)->item(0);
            if (!$q || !$a) continue;
            $question = ht_text($q->textContent);
            $inner = '';
            foreach ($a->childNodes as $c) $inner .= $d->saveHTML($c);
            $answer = strip_tags($inner, '<p><br><a><ul><ol><li><strong><em><b><i>');
            $answer = trim(preg_replace('/\s+/u', ' ', $answer));
            $answer = preg_replace('/href="\//', 'href="' . HT_SITE . '/', $answer);
            if ($question !== '' && ht_text($answer) !== '') {
                $GLOBALS['ht_schema']['faq'][] = [
                    '@type' => 'Question',
                    'name'  => $question,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
                ];
            }
        }
    }

    /** Read the visible breadcrumb trail (.breadcrumb with <a> parents and .current) from rendered
        markup so structured data matches what visitors see. */
    function ht_schema_crumbs_from_html($html) {
        if (!class_exists('DOMDocument') || stripos($html, 'breadcrumb') === false) return;
        ht_schema_state();
        $d = ht_dom($html);
        $x = new DOMXPath($d);
        $bc = $x->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' breadcrumb ')]")->item(0);
        if (!$bc) return;
        $crumbs = [];
        foreach ($x->query('.//a', $bc) as $a) {
            $href = preg_replace('/#.*$/', '', trim($a->getAttribute('href')));
            $name = ht_text($a->textContent);
            if ($name === 'Services' && ($href === '' || $href === '/')) $href = '/services'; // default parent was "/#services"
            if ($name === '' || $href === '') continue;
            $crumbs[] = [$name, strpos($href, 'http') === 0 ? $href : HT_SITE . '/' . ltrim($href, '/')];
        }
        $cur = $x->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' current ')]", $bc)->item(0);
        if ($crumbs && $cur) {
            $GLOBALS['ht_schema']['crumbs'] = $crumbs;
            $GLOBALS['ht_schema']['crumb_current'] = ht_text($cur->textContent);
        }
    }

    /** Build a Service node from a service hero (.hero--service: h1 + .lead) in rendered markup. */
    function ht_schema_service_from_html($html) {
        if (!class_exists('DOMDocument') || stripos($html, 'hero--service') === false) return;
        $d = ht_dom($html);
        $x = new DOMXPath($d);
        $hero = $x->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' hero--service ')]")->item(0);
        if (!$hero) return;
        $h1   = $x->query('.//h1', $hero)->item(0);
        $lead = $x->query(".//*[contains(concat(' ', normalize-space(@class), ' '), ' lead ')]", $hero)->item(0);
        if (!$h1) return;
        $url = HT_SITE . ht_schema_current_path();
        $node = [
            '@type'    => 'Service',
            '@id'      => $url . '#service',
            'name'     => rtrim(ht_text($h1->textContent), " ."),
            'url'      => $url,
            'provider' => ['@id' => HT_SITE . '/#organization'],
            'areaServed' => [
                ['@type' => 'City', 'name' => 'Whitby'],
                ['@type' => 'AdministrativeArea', 'name' => 'North Yorkshire'],
                ['@type' => 'Country', 'name' => 'United Kingdom'],
            ],
            'mainEntityOfPage' => ['@id' => $url . '#webpage'],
        ];
        if ($lead) $node['description'] = ht_text($lead->textContent);
        ht_schema_add($node);
    }

    function ht_schema_current_path() {
        $p = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $p = preg_replace('#/index\.php$#', '/', $p);
        if ($p !== '/' && substr($p, -1) === '/') $p = rtrim($p, '/');
        return $p;
    }

    function ht_schema_breadcrumbs($path, $url, $lastName = '') {
        $labels = [
            'services' => 'Services', 'work' => 'Case studies', 'blog' => 'Blog', 'about' => 'About', 'contact' => 'Contact',
            'website-design' => 'Website design', 'graphic-design' => 'Graphic design', 'web-development' => 'Web development',
            'search-engine-optimisation' => 'Search engine optimisation', 'social-media' => 'Social media',
            'email-marketing' => 'Email marketing', 'it-support' => 'IT support', 'it-system-design' => 'IT system design',
            'ai-integration' => 'AI integration', 'ai-search-optimisation' => 'AI search optimisation',
        ];
        $segs = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
        if (!$segs) return null;
        $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => HT_SITE . '/']];
        $acc = '';
        foreach ($segs as $i => $seg) {
            $acc .= '/' . $seg;
            $last = ($i === count($segs) - 1);
            $name = ($last && $lastName !== '') ? $lastName : ($labels[$seg] ?? ucfirst(str_replace('-', ' ', $seg)));
            $items[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $name, 'item' => HT_SITE . $acc];
        }
        return ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items];
    }

    function ht_schema_base_nodes() {
        $services = [
            ['Website design', '/services/website-design'],
            ['Graphic design', '/services/graphic-design'],
            ['Web development', '/services/web-development'],
            ['Search engine optimisation', '/services/search-engine-optimisation'],
            ['Social media', '/services/social-media'],
            ['Email marketing', '/services/email-marketing'],
            ['IT support', '/services/it-support'],
            ['IT system design', '/services/it-system-design'],
            ['AI integration', '/services/ai-integration'],
        ];
        $offers = [];
        foreach ($services as $s) {
            $offers[] = ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $s[0], 'url' => HT_SITE . $s[1]]];
        }

        // Google needs a raster logo (not SVG) for Organization rich results: if
        // /assets/logo-512.png exists, use it; otherwise fall back to the SVG.
        $png  = (isset($_SERVER['DOCUMENT_ROOT']) && is_file($_SERVER['DOCUMENT_ROOT'] . '/assets/logo-512.png'));
        $logo = HT_SITE . ($png ? '/assets/logo-512.png' : '/assets/logo-primary.svg');

        $org = [
            '@type'         => 'ProfessionalService',
            '@id'           => HT_SITE . '/#organization',
            'name'          => 'Hello Technology Ltd',
            'alternateName' => 'hellotechnology',
            'url'           => HT_SITE . '/',
            'description'   => 'Website design, SEO and AI search optimisation, AI integration and IT support for small businesses and organisations in Whitby, North Yorkshire and across the UK.',
            'logo'          => ['@type' => 'ImageObject', '@id' => HT_SITE . '/#logo', 'url' => $logo, 'contentUrl' => $logo, 'caption' => 'Hello Technology'],
            'image'         => ['@id' => HT_SITE . '/#logo'],
            'email'         => 'hi@hellotechnology.co.uk',
            'telephone'     => '+441947878108',
            'address'       => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => 'Church House, Flowergate',
                'addressLocality' => 'Whitby',
                'addressRegion'   => 'North Yorkshire',
                'addressCountry'  => 'GB',
            ],
            'areaServed'    => [
                ['@type' => 'City', 'name' => 'Whitby'],
                ['@type' => 'AdministrativeArea', 'name' => 'North Yorkshire'],
                ['@type' => 'Country', 'name' => 'United Kingdom'],
            ],
            'founder'       => ['@id' => HT_SITE . '/#jack'],
            'knowsAbout'    => ['Website design', 'Web development', 'Search engine optimisation', 'Answer engine optimisation (AEO)', 'AI search optimisation', 'AI integration', 'IT support', 'Email marketing'],
            'sameAs'        => ['https://instagram.com/_hellotechnology', 'https://www.linkedin.com/in/jack-barber-uk/'],
            'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => 'Services', 'itemListElement' => $offers],
        ];

        $person = [
            '@type'     => 'Person',
            '@id'       => HT_SITE . '/#jack',
            'name'      => 'Jack Barber',
            'url'       => HT_SITE . '/about',
            'jobTitle'  => 'Founder',
            'worksFor'  => ['@id' => HT_SITE . '/#organization'],
            'image'     => HT_SITE . '/admin/resources/jb3a-copy-w900.jpg',
            'knowsAbout' => ['Website design', 'Web development', 'Search engine optimisation', 'AI search optimisation', 'AI integration'],
            'address'   => ['@type' => 'PostalAddress', 'addressLocality' => 'Whitby', 'addressRegion' => 'North Yorkshire', 'addressCountry' => 'GB'],
            'sameAs'    => ['https://www.linkedin.com/in/jack-barber-uk/'],
        ];

        $site = [
            '@type'      => 'WebSite',
            '@id'        => HT_SITE . '/#website',
            'url'        => HT_SITE . '/',
            'name'       => 'Hello Technology',
            'inLanguage' => 'en-GB',
            'publisher'  => ['@id' => HT_SITE . '/#organization'],
        ];

        return [$org, $person, $site];
    }

    /** Capture a Perch function that echoes (or returns) a string. */
    function ht_capture($fn, $args = []) {
        if (!function_exists($fn)) return '';
        $level = ob_get_level();
        try {
            ob_start();
            $r = call_user_func_array($fn, $args);
            $o = ob_get_clean();
            return ht_text(is_string($r) && $r !== '' ? $r : $o);
        } catch (Throwable $e) {
            while (ob_get_level() > $level) ob_end_clean();
            return '';
        }
    }

    /** Print the <script type="application/ld+json"> graph. */
    function ht_schema_render() {
        $state = ht_schema_state();
        $graph = ht_schema_base_nodes();

        $ht_code = http_response_code();
        if ($ht_code === false || $ht_code === 200) {
            $page = $state['page'];
            $path = ht_schema_current_path();
            $url  = HT_SITE . ($path === '/' ? '/' : $path);

            $name = $page['name'] ?? ht_capture('perch_pages_title', [true]);
            $desc = $page['description'] ?? ht_capture('perch_page_attribute', ['description', [], true]);

            $types = ['/about' => 'AboutPage', '/contact' => 'ContactPage', '/blog' => 'CollectionPage', '/work' => 'CollectionPage', '/services' => 'CollectionPage'];
            $web = [
                '@type'      => $page['type'] ?? ($types[$path] ?? 'WebPage'),
                '@id'        => $url . '#webpage',
                'url'        => $url,
                'isPartOf'   => ['@id' => HT_SITE . '/#website'],
                'about'      => ['@id' => HT_SITE . '/#organization'],
                'inLanguage' => 'en-GB',
            ];
            if ($name) $web['name'] = $name;
            if ($desc) $web['description'] = $desc;
            if (!empty($page['image'])) $web['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $page['image']];
            if (!empty($page['datePublished'])) $web['datePublished'] = $page['datePublished'];
            if (!empty($page['dateModified']))  $web['dateModified']  = $page['dateModified'];

            if (!empty($state['crumbs'])) {
                $list = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => HT_SITE . '/']];
                foreach ($state['crumbs'] as $i => $c) {
                    if ($c[0] === 'Home') continue;
                    $list[] = ['@type' => 'ListItem', 'position' => count($list) + 1, 'name' => $c[0], 'item' => $c[1]];
                }
                $list[] = ['@type' => 'ListItem', 'position' => count($list) + 1, 'name' => $state['crumb_current'], 'item' => $url];
                $crumbs = ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $list];
            } else {
                $crumbs = ht_schema_breadcrumbs($path, $url, $page['crumb'] ?? '');
            }
            if ($crumbs) {
                $web['breadcrumb'] = ['@id' => $crumbs['@id']];
            }
            $graph[] = $web;
            if ($crumbs) $graph[] = $crumbs;

            if (!empty($state['faq'])) {
                $graph[] = [
                    '@type'      => 'FAQPage',
                    '@id'        => $url . '#faq',
                    'url'        => $url,
                    'isPartOf'   => ['@id' => $url . '#webpage'],
                    'mainEntity' => $state['faq'],
                ];
            }
            foreach ($state['nodes'] as $n) $graph[] = $n;
        }

        $ld = ['@context' => 'https://schema.org', '@graph' => $graph];
        echo '<script type="application/ld+json">'
           . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
           . "</script>\n";
    }
}
