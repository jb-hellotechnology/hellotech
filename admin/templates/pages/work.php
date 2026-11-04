<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php perch_pages_title(); ?></title>
  <?php perch_page_attributes(); ?>
  <?php perch_layout('global.head'); ?>
</head>
<body>

  <?php perch_layout('global.header'); ?>

  <main>
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
