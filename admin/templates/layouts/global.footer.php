<?php /* Site footer + Lucide icon initialiser.
   Edit the footer copy/links once here and it updates site-wide.
   Sits just before </body> in every master page template. */ ?>
<footer class="site-footer">
  <div class="container site-footer__inner">
    <div class="site-footer__top">
      <div class="site-footer__brand">
        <img src="/assets/logo-reversed.svg" alt="hellotechnology">
        <p>Friendly, expert technology for small businesses across Whitby and North Yorkshire.</p>
        <br />
        <!-- static button start -->
        <link href="https://meetings.brevo.com/assets/styles/popup.css" rel="stylesheet" />
        <script src="https://meetings.brevo.com/assets/libs/popup.min.js" type="text/javascript"></script>
        <a href="" onclick="BrevoBookingPage.initStaticButton({ url: 'https://meet.brevo.com/hellotechnology/borderless' });return false;" style="cursor: pointer; font-family: 'Work Sans', Inter; font-weight: 500; background-color: #f5b82a; color: #2b2b2b; padding: 0.5rem 1rem; border: 0px; box-shadow: rgba(0, 0, 0, 0.15) 0px -2px 0px inset; border-radius: 16px; text-decoration: none; display: inline-block;">Book a meeting</a>
        <!-- static button end -->
      </div>
      <div class="site-footer__cols">
        <div>
          <div class="foot-col__head">Services</div>
          <div class="foot-col__links">
            <a href="/services/website-design">Websites</a>
            <a href="/services/ai-integration">AI integration</a>
            <a href="/services/it-support">IT support</a>
          </div>
        </div>
        <div>
          <div class="foot-col__head">Get in touch</div>
          <div class="foot-col__links">
            <a href="mailto:hello@hellotechnology.co.uk">hi@hellotechnology.co.uk</a>
            <a href="tel:+441947878108">01947 878 108</a>
            <a href="https://instagram.com/_hellotechnology" target="_blank">Instagram</a>
            <a href="https://www.linkedin.com/in/jack-barber-uk/" target="_blank">LinkedIn</a>
            <span class="accent">Whitby &bull; North Yorkshire</span>
            <div id="wcb" class="carbonbadge wcb-d"></div>
            <script src="https://unpkg.com/website-carbon-badges@1.1.3/b.min.js" defer></script>
          </div>
        </div>
      </div>
    </div>
    <div class="site-footer__bar">
      <span>&copy; <?php echo date('Y'); ?> Hello Technology Ltd &bull; Church House, Flowergate, Whitby</span>
      <span>Built by a human, made in Whitby.</span>
    </div>
  </div>
</footer>

<?php
  // LocalBusiness structured data — shared site-wide (every page includes this footer).
  // ProfessionalService extends LocalBusiness/Organization, so this is a strict
  // superset of a plain Organization entry — better fit for local search/Maps.
  $business_ld = [
    '@context'      => 'https://schema.org',
    '@type'         => 'ProfessionalService',
    'name'          => 'Hello Technology Ltd',
    'alternateName' => 'hellotechnology',
    'url'           => 'https://hellotechnology.co.uk',
    'logo'          => 'https://hellotechnology.co.uk/assets/logo-primary.svg',
    'image'         => 'https://hellotechnology.co.uk/assets/logo-primary.svg',
    'email'         => 'hi@hellotechnology.co.uk',
    'telephone'     => '+441947878108',
    'areaServed'    => 'Whitby and North Yorkshire',
    'address'       => [
      '@type'           => 'PostalAddress',
      'streetAddress'   => 'Church House, Flowergate',
      'addressLocality' => 'Whitby',
      'addressRegion'   => 'North Yorkshire',
      'addressCountry'  => 'GB',
    ],
    'sameAs' => [
      'https://instagram.com/_hellotechnology',
      'https://www.linkedin.com/in/jack-barber-uk/',
    ],
  ];
?>
<script type="application/ld+json"><?php echo json_encode($business_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>

<script src="https://cdn.usefathom.com/script.js" data-site="VPKMAXJJ" defer></script>

<script>
  function initIcons(){
    try { if (window.lucide && window.lucide.createIcons) window.lucide.createIcons(); } catch (e) {}
  }
  document.addEventListener('DOMContentLoaded', initIcons);
  window.addEventListener('load', initIcons);
</script>

<script>
  // Mobile pull-out menu
  (function () {
    var toggle   = document.querySelector('.nav__toggle');
    var panel    = document.getElementById('mobile-nav');
    var backdrop = document.querySelector('.mobile-nav__backdrop');
    if (!toggle || !panel) return;

    function setOpen(open) {
      document.body.classList.toggle('nav-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    toggle.addEventListener('click', function () {
      setOpen(!document.body.classList.contains('nav-open'));
    });
    if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });

    // Close when a menu link is followed
    panel.addEventListener('click', function (e) {
      if (e.target.closest('a')) setOpen(false);
    });

    // Close on Escape, and when resizing up to desktop
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') setOpen(false);
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 860) setOpen(false);
    });
  })();
</script>
