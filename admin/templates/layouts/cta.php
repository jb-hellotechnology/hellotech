<?php /* Reusable closing call-to-action band.
   Override per page with:
   perch_layout('cta', ['heading' => '…', 'text' => '…', 'email' => '…', 'tel' => '…']); */ ?>
<section id="contact" class="section section--md section--accent scroll-anchor">
  <div class="container cta">
    <div class="cta__copy">
      <h2 class="h2 h2--lg"><?php echo perch_layout_var('heading', true) ?: 'Got a project, or just a question?'; ?></h2>
      <p><?php echo perch_layout_var('text', true) ?: "Drop me a line and you'll get a friendly, jargon-free reply from me — usually the same day."; ?></p>
    </div>
    <div class="cta__actions">
      <?php $email = perch_layout_var('email', true) ?: 'hi@hellotechnology.co.uk'; ?>
      <?php $tel   = perch_layout_var('tel', true) ?: '01947 878 108'; ?>
      <a class="btn btn--dark" href="mailto:<?php echo $email; ?>"><i data-lucide="mail"></i> <?php echo $email; ?></a>
      <a class="cta__tel" href="tel:<?php echo preg_replace('/\s+/', '', $tel); ?>"><i data-lucide="phone"></i> <?php echo $tel; ?></a>
    </div>
  </div>
</section>
