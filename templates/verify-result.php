<?php 
get_header(); 
$code = sanitize_text_field(get_query_var('wvs_code')); 
$data = WVS_Verification::verify_code($code); 
?>
<div class="wvs-verify" style="max-width:720px;margin:40px auto;padding:20px;border:1px solid #eee;border-radius:8px;">
  <h1>Warranty Verification</h1>
  <form method="get" action="<?php echo esc_url(home_url('/verify/')); ?>" style="display:flex;gap:10px;">
    <input type="text" name="wvs_code" value="<?php echo esc_attr($code); ?>" placeholder="Enter Warranty Number or Phone Number" class="input" style="flex:1;padding:10px;">
    <button class="button" type="submit">Verify</button>
  </form>
  
  <?php if(!$data): ?>
    <div style="margin-top:20px;padding:12px;background:#fee;border:1px solid #f99;border-radius:6px;">
      <strong>Not found.</strong> Please check the warranty number or phone number and try again.
    </div>
  <?php else: ?>
    <div style="margin-top:20px;padding:12px;background:#f6fff6;border:1px solid #b6e0b6;border-radius:6px;">
      <?php if (!empty($data['multiple_warranties'])): ?>
        <div style="margin-bottom:15px;padding:8px;background:#fff3cd;border:1px solid #ffeaa7;border-radius:4px;">
          <strong>Note:</strong> Found <?php echo intval($data['multiple_warranties']); ?> warranties for this phone number. Showing the most recent one.
        </div>
      <?php endif; ?>
      
      <p><strong>Status:</strong> <?php echo esc_html($data['status']); ?></p>
      <p><strong>Warranty Number:</strong> <?php echo esc_html($data['number']); ?></p>
      <p><strong>Order ID:</strong> <?php echo esc_html($data['order_id']); ?></p>
      <p><strong>Product ID:</strong> <?php echo esc_html($data['product_id']); ?></p>
      <p><strong>Customer Email:</strong> <?php echo esc_html($data['email']); ?></p>
      <?php if (!empty($data['phone'])): ?>
      <p><strong>Customer Phone:</strong> <?php echo esc_html($data['phone']); ?></p>
      <?php endif; ?>
      <p><strong>Start:</strong> <?php echo esc_html($data['start']); ?> &nbsp; <strong>End:</strong> <?php echo esc_html($data['end']); ?></p>
      <p><a class="button" href="<?php echo esc_url(WVS_Certificate::certificate_url($data['id'])); ?>" target="_blank">View Certificate</a> <a class="button" href="<?php echo esc_url(WVS_Invoice::invoice_url($data['id'])); ?>" target="_blank">View Invoice</a></p>
      <div style="margin-top:10px;"><img src="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . rawurlencode($data['verify_url'])); ?>" alt="QR Code"><p style="font-size:12px;color:#666;">Scan to verify: <?php echo esc_html($data['verify_url']); ?></p></div>
    </div>
  <?php endif; ?>
</div>
<?php get_footer(); ?>