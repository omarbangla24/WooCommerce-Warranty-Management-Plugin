<?php get_header(); ?>
<div class="wvs-verify" style="max-width:720px;margin:40px auto;padding:20px;border:1px solid #eee;border-radius:8px;">
  <h1>Warranty Verification</h1>
  <form method="get" action="<?php echo esc_url(home_url('/verify/')); ?>" style="display:flex;gap:10px;">
    <input type="text" name="wvs_code" placeholder="Enter Warranty Number or Phone Number" class="input" style="flex:1;padding:10px;">
    <button class="button" type="submit">Verify</button>
  </form>
  <?php if (!empty($_GET['wvs_code'])): ?>
    <?php $code = sanitize_text_field($_GET['wvs_code']); wp_redirect( home_url('/verify/'.urlencode($code)) ); exit; ?>
  <?php endif; ?>
  <div style="margin-top:15px;">
    <p><strong>Search by:</strong></p>
    <ul style="margin:5px 0 0 20px;">
      <li><strong>Warranty Number:</strong> Format like <code>WVS-YYYYMMDD-ORDER-RANDOM</code></li>
      <li><strong>Phone Number:</strong> Enter your registered phone number</li>
    </ul>
  </div>
</div>
<?php get_footer(); ?>