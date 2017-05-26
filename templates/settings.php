<?php
/**
 * @package Stripe
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 */
?>
<form method="post" enctype="multipart/form-data" class="form-horizontal">
  <input type="hidden" name="token" value="<?php echo $_token; ?>">
  <div class="panel panel-default">
    <div class="panel-body">
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Status'); ?></label>
        <div class="col-md-6">
          <div class="btn-group" data-toggle="buttons">
            <label class="btn btn-default<?php echo empty($settings['status']) ? '' : ' active'; ?>">
              <input name="settings[status]" type="radio" autocomplete="off" value="1"<?php echo empty($settings['status']) ? '' : ' checked'; ?>>
              <?php echo $this->text('Enabled'); ?>
            </label>
            <label class="btn btn-default<?php echo empty($settings['status']) ? ' active' : ''; ?>">
              <input name="settings[status]" type="radio" autocomplete="off" value="0"<?php echo empty($settings['status']) ? ' checked' : ''; ?>>
              <?php echo $this->text('Disabled'); ?>
            </label>
          </div>
          <div class="help-block">
            <?php echo $this->text('Disabled payment methods will be hidden on checkout page'); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Test mode'); ?></label>
        <div class="col-md-6">
          <div class="btn-group" data-toggle="buttons">
            <label class="btn btn-default<?php echo empty($settings['test']) ? '' : ' active'; ?>">
              <input name="settings[test]" type="radio" autocomplete="off" value="1"<?php echo empty($settings['test']) ? '' : ' checked'; ?>>
              <?php echo $this->text('Enabled'); ?>
            </label>
            <label class="btn btn-default<?php echo empty($settings['test']) ? ' active' : ''; ?>">
              <input name="settings[test]" type="radio" autocomplete="off" value="0"<?php echo empty($settings['test']) ? ' checked' : ''; ?>>
              <?php echo $this->text('Disabled'); ?>
            </label>
          </div>
          <div class="help-block">
            <?php echo $this->text('Test mode is intended for testing purposes and should be disabled to send real payments'); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Order status'); ?></label>
        <div class="col-md-4">
          <select name="settings[order_status_success]" class="form-control">
            <?php foreach ($statuses as $status_id => $status_name) { ?>
            <option value="<?php echo $this->escape($status_id); ?>"<?php echo isset($settings['order_status_success']) && $settings['order_status_success'] == $status_id ? ' selected' : ''; ?>><?php echo $this->escape($status_name); ?></option>
            <?php } ?>
          </select>
          <div class="help-block">
            <?php echo $this->text('The status will be assigned to an order after successful transaction'); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Test secret key'); ?></label>
        <div class="col-md-4">
          <input name="settings[test_key]" class="form-control" value="<?php echo isset($settings['test_key']) ? $this->escape($settings['test_key']) : ''; ?>">
          <div class="help-block">
            <?php echo $this->text('The secret key is used for API calls on the server-side for testing purposes'); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Test publishable key'); ?></label>
        <div class="col-md-4">
          <input name="settings[test_public_key]" class="form-control" value="<?php echo isset($settings['test_public_key']) ? $this->escape($settings['test_public_key']) : ''; ?>">
          <div class="help-block">
            <?php echo $this->text('The publishable key is used to generate credit card tokens for testing purposes'); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Live secret key'); ?></label>
        <div class="col-md-4">
          <input name="settings[live_key]" class="form-control" value="<?php echo isset($settings['live_key']) ? $this->escape($settings['live_key']) : ''; ?>">
          <div class="help-block">
            <?php echo $this->text('The secret key is used for API calls on the server-side'); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label class="col-md-2 control-label"><?php echo $this->text('Live publishable key'); ?></label>
        <div class="col-md-4">
          <input name="settings[live_public_key]" class="form-control" value="<?php echo isset($settings['live_public_key']) ? $this->escape($settings['live_public_key']) : ''; ?>">
          <div class="help-block">
              <?php echo $this->text('The publishable key is used to generate credit card tokens'); ?>
          </div>
        </div>
      </div>
      <div class="form-group">
        <div class="col-md-4 col-md-offset-2">
          <div class="btn-toolbar">
            <a href="<?php echo $this->url('admin/module/list'); ?>" class="btn btn-default"><i class="fa fa-reply"></i> <?php echo $this->text('Cancel'); ?></a>
            <button class="btn btn-default save" name="save" value="1">
              <i class="fa fa-floppy-o"></i> <?php echo $this->text('Save'); ?>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>