<form method="post" id="stripe-payment-form" class="form-horizontal">
  <p><?php echo $this->text('To process your order we need to get your payment in advance'); ?></p>
  <p class="payment-errors"></p>
  <div class="form-group">
    <label class="col-md-2 control-label"><?php echo $this->text('Card number'); ?></label>
    <div class="col-md-4">
      <input class="form-control" data-stripe="number">
    </div>
  </div>
  <div class="form-group">
    <label class="col-md-2 control-label"><?php echo $this->text('Expiration (MM/YY)'); ?></label>
    <div class="col-md-1">
      <input class="form-control" data-stripe="exp_month" placeholder="<?php echo $this->text('MM'); ?>">
    </div>
    <div class="col-md-1">
      <input class="form-control" data-stripe="exp_year" placeholder="<?php echo $this->text('YY'); ?>">
    </div>
  </div>
  <div class="form-group">
    <label class="col-md-2 control-label"><?php echo $this->text('CVC'); ?></label>
    <div class="col-md-2">
      <input class="form-control" data-stripe="cvc">
    </div>
  </div>
  <div class="form-group">
    <div class="col-md-2 col-md-offset-2">
      <input type="submit" class="btn btn-default" value="<?php echo $this->text('Pay @amount', array('@amount' => $order['total_formatted'])); ?>">
    </div>
  </div>
</form>
