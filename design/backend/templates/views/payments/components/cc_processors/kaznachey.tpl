{* $Id: kaznachey.tpl  $cas *}

<div class="control-group">
	<label class="control-label" for="merchantid">merchantGuid:</label>
  <div class="controls">
    <input type="text" name="payment_data[processor_params][kaznachey_merchantGuid]" id="kaznachey_merchantGuid" value="{$processor_params.kaznachey_merchantGuid}" class="input-text" />
  </div>
</div>

<div class="control-group">
	<label class="control-label" for="details">merchnatSecretKey:</label>
  <div class="controls">
    <input type="text" name="payment_data[processor_params][kaznachey_merchnatSecretKey]" id="secret" value="{$processor_params.kaznachey_merchnatSecretKey}" class="input-text" size="100" />
  </div>
</div>

<!--
<div class="control-group">
	<label class="control-label" for="kaznachey_mode">Payment Type:</label>
  <div class="controls">
    <select name="payment_data[processor_params][kaznachey_payment_type]" id="kaznachey_payment_type">
        <option value="payment" {if $processor_params.kaznachey_payment_type == "payment"}selected="selected"{/if}>{__("Payment")}</option>
        <option value="authorization" {if $processor_params.kaznachey_payment_type == "authorization"}selected="selected"{/if}>{__("Authorization")}</option>    
    </select>
  </div>
</div>
-->

