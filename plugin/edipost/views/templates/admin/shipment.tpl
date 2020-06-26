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
        {l s='Edipost Integration' }
    </div>
    <div class="edipost-wrapper">
        <div class="error-block" id="error-block"></div>
        <form action="" method="post">
            <div id="edipost" class="form-horizontal">
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Choose shiping method' }</label>
                    <div class="col-lg-9">
                        <select class="chosen form-control" name="edipost_ship_method" id="edipost_ship_method">
                            {if $shipping_methods['options']}
                                {foreach $shipping_methods['options'] as $product}
                                    <option
                                            {if $product['status'] != 'Available'}
                                        disabled
                                    {/if}
                                            data-status ={$product['status']}
                                            data-service="{$product['service']}" value="{$product['id']}">{$product['name']}</option>
                                {/foreach}
                            {/if}

                        </select>


                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Reference text optional' }</label>
                    <div class="col-lg-9">
                        <textarea id="txt_msg" class="textarea-autosize" name="message"
                                  style="overflow: hidden; overflow-wrap: break-word; resize: none; height: 60px;"></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Electronic alert on SMS/Email' }</label>
                    <div class="col-lg-9">
                        <input type="checkbox" name="e_alert" id="e_alert" value="1" class="noborder">
                    </div>
                </div>


                <input type="hidden" id="id_order" name="id_order" value="{$order_id}">
                <input type="hidden" id="id_customer" name="id_customer" value="{$customer_id}">
                <div class="form-group">
                    <div class="pull-right">
                        <button type="button" id="edipost-create" class="btn btn-primary pull" name="submitEdipost">
                            {l s='Create shipment' }
                        </button>
                        <button type="button" id="edipost-open" class="btn btn-primary pull" name="openEdipost">
                            {l s='Open in Edipost' }
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

