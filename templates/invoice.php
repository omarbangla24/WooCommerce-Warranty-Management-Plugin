<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>Invoice</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      background: #f6f7fb;
      margin: 0;
      padding: 0;
    }

    .wrap {
      max-width: 800px;
      margin: 20px auto;
      background: #fff;
      border: 1px solid #eee;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 6px 24px rgba(0, 0, 0, .06)
    }

    .hdr {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 20px 24px;
      border-bottom: 1px solid #eee
    }

    .hdr img {
      max-height: 60px
    }

    .content {
      padding: 24px
    }

    .info-row {
      display: grid;
      grid-template-columns: 1fr 1fr 200px;
      gap: 16px;
      margin-bottom: 16px
    }

    .muted {
      color: #666;
      font-size: 13px
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px
    }

    th,
    td {
      padding: 10px;
      border-bottom: 1px solid #eee;
      text-align: left
    }

    .footer {
      padding: 16px 24px;
      border-top: 1px solid #eee;
      color: #666;
      font-size: 13px
    }

    .policy {
      margin-top: 16px;
      padding: 12px;
      border: 1px dashed #ddd;
      border-radius: 8px;
      background: #fafafa
    }

    .qr {
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column
    }

    .qr img {
      width: 80px;
      height: 80px
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
      }

      .wrap {
        box-shadow: none;
        border: 1px solid #ccc;
        margin: 0 0 5mm 0;
        page-break-inside: avoid;
        height: 48vh;
        overflow: hidden;
      }

      .wrap:nth-child(2n) {
        margin-bottom: 0;
      }

      .content {
        padding: 16px;
      }

      .hdr {
        padding: 12px 16px;
      }

      .qr img {
        width: 80px;
        height: 80px;
      }

      .policy {
        font-size: 11px;
        padding: 8px;
      }
    }

    @media (max-width: 700px) {
      .info-row {
        grid-template-columns: 1fr;
        gap: 12px;
      }
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="hdr">
      <div style="display:flex;align-items:center;gap:12px;">
        <?php if (!empty($logo)): ?><img src="<?php echo esc_url($logo); ?>" alt="Logo"><?php endif; ?>
        <div>
          <div style="font-size:20px;font-weight:700">Invoice</div>
          <div class="muted"><?php echo $company ?: 'Company information is not set.'; ?></div>
        </div>
      </div>
      <div class="muted">
        <div><strong>Warranty #:</strong> <?php echo esc_html($number); ?></div>
        <?php if (!empty($order_id)): ?><div><strong>Order #:</strong> <?php echo esc_html($order_id); ?></div><?php endif; ?>
        <?php if (!empty($order_date)): ?><div><strong>Date:</strong> <?php echo esc_html($order_date); ?></div><?php endif; ?>
      </div>
    </div>
    <div class="content">
      <div class="info-row">
        <div>
          <h4 style="margin:0 0 8px 0;">Bill To</h4>
          <p style="margin:0;font-size:14px;">
            <?php if (!empty($billing_address)) echo esc_html($billing_address); ?>
            <?php if (!empty($billing_phone)) echo esc_html($billing_phone); ?>
          </p>
        </div>
        <div>
          <h4 style="margin:0 0 8px 0;">Payment</h4>
          <p style="margin:0;font-size:14px;"><strong>Total:</strong> <?php echo $total ?: ''; ?>
          
          <br>
          </p>
        </div>
        <div class="qr">
          <?php if (!empty($verify_url)): ?>
            <img src="<?php echo esc_url('https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($verify_url)); ?>" alt="QR">
            <div class="muted" style="text-align:center;font-size:11px;margin-top:4px;">Scan to verify</div>
          <?php endif; ?>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Unit Price</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr style="<?php echo !empty($it['is_target']) ? 'background:#f9fffa;' : ''; ?>">
              <td style="margin:0;font-size:14px;"><?php echo esc_html($it['name']); ?></td>
              <td style="margin:0;font-size:14px;"><?php echo esc_html($it['qty']); ?></td>
              <td style="margin:0;font-size:14px;"><?php echo $it['unit']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php $policy = get_option('wvs_policy_text');
      if ($policy): ?>
        <div class="policy">
          <strong style="margin:0;font-size:15px;">ওয়ারেন্টি নীতিমালা</strong>
          <div style="white-space:pre-wrap; margin-top:6px;font-size:12px;"><?php echo esc_html($policy); ?></div>
        </div>
      <?php endif; ?>

      
    </div>
    <div class="footer">
      <?php echo $footer ? esc_html($footer) : 'Thank you for your purchase.'; ?>
      <span style="float:right;">Verify Link: <?php echo (($verify_url)); ?></span>
    </div>
  </div>
</body>

</html>