jQuery(function($){
  function addMonths(dateStr, months){
    var d = new Date(dateStr);
    if(isNaN(d)) d = new Date();
    d.setMonth(d.getMonth() + months);
    var y = d.getFullYear();
    var m = ('0' + (d.getMonth() + 1)).slice(-2);
    var day = ('0' + d.getDate()).slice(-2);
    return y + '-' + m + '-' + day;
  }
  
  // Initialize datepicker
  if ($.fn.datepicker){
    $('.wvs-datepick').each(function(){
      try{
        $(this).datepicker({ dateFormat: 'yy-mm-dd' });
      }catch(e){}
    });
  }
  
  // Load from order functionality
  $('#wvs_load_from_order').on('click', function(){
    var oid = $('#wvs_order_select').val();
    if(!oid){
      alert('Select an order first.');
      return;
    }
    
    $.post(WVS_META.ajaxurl, {
      action: 'wvs_order_details',
      nonce: WVS_META.nonce,
      order_id: oid
    }, function(resp){
      if(!resp || !resp.success){
        alert((resp && resp.data && resp.data.message) ? resp.data.message : 'Failed to load order.');
        return;
      }
      
      var d = resp.data;
      $('#wvs_order_id').val(d.order_id);
      $('#wvs_customer_email').val(d.billing_email);
      $('#wvs_customer_phone').val(d.billing_phone || '');
      $('#wvs_start_date').val(d.order_date);
      
      var $sel = $('#wvs_product_select').empty();
      $sel.append($('<option>').val('').text('Select product…'));
      
      (d.items || []).forEach(function(it){
        var label = '#' + it.product_id + ' — ' + it.product_name;
        if(it.warranty_months) {
          label += ' (' + it.warranty_months + ' mo)';
        }
        
        var opt = $('<option>')
          .val(it.product_id)
          .text(label)
          .attr('data-wmonths', it.warranty_months || 0);
        $sel.append(opt);
      });
    });
  });
  
  // Product selection with warranty calculation
  $('#wvs_product_select').on('change', function(){
    var pid = $(this).val();
    if(!pid) return;
    
    $('#wvs_product_id').val(pid);
    var months = parseInt($(this).find(':selected').attr('data-wmonths') || '0', 10);
    
    if(months > 0){
      var start = $('#wvs_start_date').val() || new Date().toISOString().slice(0,10);
      $('#wvs_end_date').val(addMonths(start, months));
    }
  });
  
  // Recalc on start date change
  $('#wvs_start_date').on('change', function(){
    var months = parseInt($('#wvs_product_select').find(':selected').attr('data-wmonths') || '0', 10);
    if(months > 0){
      var start = $('#wvs_start_date').val() || new Date().toISOString().slice(0,10);
      $('#wvs_end_date').val(addMonths(start, months));
    }
  });
  
  // Send email functionality
  $(document).on('click','#wvs_send_email_btn',function(){
    var wid = $(this).data('id');
    if(!wid){
      alert('Missing warranty ID');
      return;
    }
    
    var $b = $(this);
    $b.prop('disabled', true).text('Sending...');
    
    $.post(WVS_META.ajaxurl, {
      action: 'wvs_send_warranty_email',
      nonce: WVS_META.send_nonce,
      warranty_id: wid
    }, function(resp){
      alert(resp && resp.success ? 'Email sent to customer.' : 
        (resp && resp.data && resp.data.message ? resp.data.message : 'Failed to send email.'));
      $b.prop('disabled', false).text('Send Email to Customer');
    });
  });
  
  // Generate warranty number
  $('#wvs_generate_number').on('click', function(){
    var t = new Date(),
        y = t.getFullYear(),
        m = ('0' + (t.getMonth() + 1)).slice(-2),
        d = ('0' + t.getDate()).slice(-2);
    
    var rand = Math.random().toString(36).slice(2, 4).toUpperCase();
    var order = $('#wvs_order_id').val() || '0';
    
    $('#wvs_number').val(order + '-' + rand);
  });
});