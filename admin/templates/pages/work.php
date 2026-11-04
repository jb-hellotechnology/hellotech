<?php
  require_once __DIR__ . '/../layouts/schema-lib.php';

  // Case-study detail pages (/work/{slug}) share this template with the /work index, so the
  // page-level title/description would otherwise be identical on every case study.
  // Fetch the item first and use its own title + intro.
  $case = null;
  if (perch_get('s')) {
      try {
          $found = perch_collection('Work', [
              'filter' => 'slug', 'match' => 'eq', 'value' => perch_get('s'),
              'count' => 1, 'skip-template' => true,
          ], true);
          if (is_array($found) && !empty($found[0])) $case = $found[0];
      } catch (Throwable $e) { $case = null; }
  }
  $case_title = $case ? ht_text(ht_val($case['title'] ?? '')) : '';
  $case_intro = $case ? ht_text(ht_val($case['intro'] ?? '')) : '';
  if (mb_strlen($case_intro) > 160) $case_intro = rtrim(mb_substr($case_intro, 0, 157)) . '…';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php if ($case_title !== ''): ?>
    <title><?php echo htmlspecialchars($case_title, ENT_QUOTES, 'UTF-8'); ?> | Case study | Hello Technology</title>
    <?php if ($case_intro !== ''): ?><meta name="description" content="<?php echo htmlspecialchars($case_intro, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
    <?php
      $case_url = HT_SITE . ht_schema_current_path();
      ht_schema_page(['name' => $case_title, 'description' => $case_intro, 'crumb' => $case_title]);
      $creative = [
          '@type'     => 'CreativeWork',
          '@id'       => $case_url . '#case-study',
          'name'      => $case_title,
          'url'       => $case_url,
          'creator'   => ['@id' => HT_SITE . '/#organization'],
          'author'    => ['@id' => HT_SITE . '/#jack'],
          'mainEntityOfPage' => ['@id' => $case_url . '#webpage'],
          'inLanguage' => 'en-GB',
          'genre'     => 'Case study',
      ];
      if ($case_intro !== '') $creative['description'] = $case_intro;
      if (!empty($case['_date']) && strtotime(ht_val($case['_date']))) $creative['datePublished'] = date('c', strtotime(ht_val($case['_date'])));
      ht_schema_add($creative);
    ?>
  <?php else: ?>
    <title><?php perch_pages_title(); ?></title>
    <?php perch_page_attributes(); ?>
  <?php endif; ?>
  <?php perch_layout('global.head'); ?>
</head>
<body>

  <?php perch_layout('global.header'); ?>

  <main id="main" tabindex="-1">
    <?php
      if(perch_get('s')){
    
          perch_collection('Work', [
            'filter'   => 'slug',
            'match'    => 'eq',
            'value'    => perch_get('s'),
            'template' => 'case_study.html'
          ]);
      perch_content('Page Content Lower');
    ?>
    
    <!-- MORE WORK -->
    <section class="section section--xs section--white-t">
      <div class="container">
        <div class="section-head--row mb-8">
          <h2 class="h2 h2--28">More recent work</h2>
          <a class="link" href="/work">See all <i data-lucide="arrow-right"></i></a>
        </div>
        <div class="grid grid--2">
          <?php
          perch_collection('Work', [
              'count' => 2,
              'template' => 'case_study-card.html',
              'sort-order' => 'RAND',
              'sort' => '_date'
          ]);
          ?>
        </div>
      </div>
    </section>
    <?php 
      }else{
        perch_content('Page Content');
        ?>
        <section class="section section--white-t">
          <div class="container">
            <?php
            perch_collection('Work', [
                'count' => 10,
                'template' => 'case_study-preview.html',
                'sort-order' => 'DESC',
                'sort' => '_date'
            ]);
            ?>
          </div>
        </section>
        <?php
        perch_content('Page Content Lower');
      }
    ?>
  </main>

  <?php perch_layout('global.footer'); ?>

</body>
</html>
