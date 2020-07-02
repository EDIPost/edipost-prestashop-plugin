{*
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
    <div class="panel-heading">
        {l s='Edipost Integration' mod='edipost'}
    </div>
    <div class="edipost-wrapper">
        <div id="error-block"
                {if !$error_text } style="display: none;"  {/if}
             class="error-block">
            <div class="alert alert-warning">
                {$error_text }
            </div>
        </div>

        <form action="" method="post">
            <div style="display: none" class="loader" id="loader-1"></div>
            <div id="edipost" class="form-horizontal">
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Choose shipping method' mod='edipost'}</label>
                    <div class="col-lg-9">
                        <select class="chosen form-control" name="edipost_ship_method" id="edipost_ship_method" autocomplete="off">
                            {if $shipping_methods['options']}
                                {foreach $shipping_methods['options'] as $product}
                                    {assign var='undescore' value="_" }'
                                    {assign var='curr_product' value=$product['service']|cat:"_"|cat:$product['id']}
                                    <option
                                            {if $curr_product == $prev_product}selected{/if}
                                            {if $product['status'] != 'Available'}disable{/if}
                                            data-status ="{$product['status']}"
                                            data-service="{$product['service']}" value="{$product['id']}">{$product['name']}</option>
                                {/foreach}
                            {/if}

                        </select>


                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Reference text (optional)' mod='edipost'}</label>
                    <div class="col-lg-9">
                        <textarea id="edipost_reference" class="textarea-autosize" name="message" maxlength="35"
                                  style="overflow: hidden; overflow-wrap: break-word; resize: none; height: 60px;" autocomplete="off">{l s='Order' mod='edipost'} #{$order_id}</textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Electronic alert on SMS/Email' mod='edipost'}</label>
                    <div class="col-lg-9" style="padding-top: 6px;">
                        <input type="checkbox" name="edipost_e_alert" id="edipost_e_alert" value="1" class="noborder" checked>
                    </div>
                </div>


                <input type="hidden" id="id_order" name="id_order" value="{$order_id}">
                <input type="hidden" id="id_customer" name="id_customer" value="{$customer_id}">
                <div class="form-group">
                    <div class="pull-right">
                        <button type="button" id="edipost-create" class="btn btn-primary pull" name="submitEdipost" disabled>
                            {l s='Create shipment' mod='edipost'}
                        </button>
                        <button type="button" id="edipost-open" class="btn btn-primary pull" name="openEdipost"
                        {if !$config['EDIPOST_USERNAME'] || !$config['EDIPOST_PASSWORD']} disabled{/if}>
                            {l s='Open in Edipost' mod='edipost'}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>


