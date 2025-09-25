<div class="wrap invoice-block">
  <div class="hdr" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-bottom:1px solid #eee">
    <div style="display:flex;align-items:center;gap:10px;">
      <?php if (!empty($logo)): ?><img src="<?php echo esc_url($logo); ?>" alt="Logo" style="max-height:60px"><?php endif; ?>
      <div>
        <div style="font-size:16px;font-weight:700">Invoice</div>
        <div style="color:#666;font-size:11px"><?php echo $company ?: 'Company information is not set.'; ?></div>
      </div>
    </div>
    <div style="color:#666;font-size:11px">
      <div><strong>Warranty #:</strong> <?php echo esc_html($number); ?></div>
      <?php if (!empty($order_id)): ?><div><strong>Order #:</strong> <?php echo esc_html($order_id); ?></div><?php endif; ?>
      <?php if (!empty($order_date)): ?><div><strong>Date:</strong> <?php echo esc_html($order_date); ?></div><?php endif; ?>
    </div>
  </div>
  <div class="content" style="padding:14px">
    <div style="display:grid;grid-template-columns:1fr 1fr 120px;gap:10px;margin-bottom:10px">
      <div>
        <h5 style="margin:0 0 4px 0;font-size:13px;">Bill To</h5> 
        <p style="margin:0;font-size:12px;">
<?php if (!empty($billing_address)) echo esc_html($billing_address); ?> , 
       <?php if (!empty($billing_phone)) echo esc_html($billing_phone); ?>
</p>
      </div>
      <div>
        <h5 style="margin:0 0 4px 0;font-size:13px;">Payment</h5>
        <p style="margin:0;font-size:12px;"><strong>Total:</strong> <?php echo $total ?: ''; ?><br>
      </p>
      </div>
      <div style="text-align:center;">
        <?php if (!empty($verify_url)): ?>
          <img src="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . rawurlencode($verify_url)); ?>" alt="QR" style="width:70px;height:70px">
          <div style="color:#666;font-size:9px;margin-top:2px;">Scan to Verify</div>
        <?php endif; ?>
      </div>
    </div>

    <table style="width:100%;border-collapse:collapse;margin-top:6px">
      <thead><tr><th style="text-align:left;border-bottom:1px solid #eee;padding:4px;font-size:12px">Item</th><th style="text-align:left;border-bottom:1px solid #eee;padding:4px;font-size:12px">Qty</th><th style="text-align:left;border-bottom:1px solid #eee;padding:4px;font-size:12px">Unit</th></tr></thead>
      <tbody>
        <?php foreach($items as $it): ?>
          <tr style="<?php echo !empty($it['is_target']) ? 'background:#f9fffa;' : ''; ?>">
            <td style="padding:4px;border-bottom:1px solid #f3f3f3;font-size:11px"><?php echo esc_html($it['name']); ?></td>
            <td style="padding:4px;border-bottom:1px solid #f3f3f3;font-size:11px"><?php echo esc_html($it['qty']); ?></td>
            <td style="padding:4px;border-bottom:1px solid #f3f3f3;font-size:11px"><?php echo $it['unit']; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php $policy = get_option('wvs_policy_text'); if ($policy): ?>
      <div style="margin-top:6px;padding:6px;border:1px dashed #ddd;border-radius:4px;background:#fafafa">
        <strong style="font-size:11px;">Warranty / Return Policy</strong>
        <div style="white-space:pre-wrap; margin-top:3px; font-size:10px;"><?php echo esc_html($policy); ?></div>
      </div>
    <?php endif; ?>
    
    <div style="margin-top:6px;font-size:9px;color:#999;">
       <?php echo $footer ? esc_html($footer) : 'Thank you for your purchase.'; ?>
      <span style="float:right;">Verify Link: <?php echo (($verify_url)); ?></span></div>
  </div>
</div>