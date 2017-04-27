{*
* 2017 mpSOFT
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
*  @author    mpSOFT <info@mpsoft.it>
*  @copyright 2017 mpSOFT Massimiliano Palermo
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of mpSOFT
*}

<pre>
    STATUS:
    {$status|@print_r}
</pre>
<style>
    .se-pre-con {
            position: fixed;
            left: 0px;
            top: 0px;
            width: 100%;
            height: 100%;
            z-index: 999999;
            background: url('../modules/mpimportproducts/views/img/cube.gif') center no-repeat #fff;
            display: none;
    }
</style>
<div class="se-pre-con"></div>
<form class='defaultForm form-horizontal' method='post' id="form_export_invoices" enctype="multipart/form-data">
    <div class="panel">
        <div class="panel-heading">
            <span>
                <i class="icon-list"></i>
                {l s='Import Products' mod='mpimportproducts'}
            </span>
        </div>
        <!-- INPUT SECTION -->
        <div>
            <div class="form-group">
                <label for="fileUpload">{l s='Select file'} <sup>*</sup></label>
                <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
                <input type="file" class="required form-control" id="input_file_upload" name="input_file_upload" accept='.xls'/>
            </div>
            <br>
        </div>
        <div class='panel-footer'>
            <button type="button" value="1" id="button_file_upload" name="button_file_upload" class="btn btn-default pull-right">
                <i class="icon-2x icon-upload-alt"></i> 
                {l s='Upload Excel' mod='mpimportproducts'}
            </button>
            <button type="submit" value="1" id="submit_file_upload" name="submit_file_upload" style='display:none;'>
                <i class="icon-2x icon-upload-alt"></i> 
                {l s='Upload Excel' mod='mpimportproducts'}
            </button>
        </div>
    </div>
    <!-- TABLE SECTION -->
    {if !empty($rows)}
    <div class='panel'>
        <div class='panel-heading'>
            <span>
                <i class='icon-table'></i>
                {l s='Product List' mod='mpimportproducts'}
            </span>
        </div>
        <div class='panel-footer'>
            <button type="button" value="1" id="button_file_import" name="button_file_import" class="btn btn-default pull-right">
                <i class="icon-download"></i> 
                {l s='Import products' mod='mpimportproducts'}
            </button>
            <button type="submit" value="1" id="submit_file_import" name="submit_file_import" style='display:none;'>
                <i class="icon-download"></i> 
                {l s='Import products' mod='mpimportproducts'}
            </button>
        </div>
        <div class='panel-body'>
            {$pagination}
            <br>
            {$navigator}
        </div>
        <div class='panel-footer'>
            <button type="submit" value="1" id="submit_file_import" name="submit_file_import" class="btn btn-default pull-right">
                <i class="icon-download"></i> 
                {l s='Import products' mod='mpimportproducts'}
            </button>
        </div>
    </div>
    {/if}
</form>

<script type="text/javascript">
    $(window).bind("load",function()
    {    
        $("#checkAll").on("change",function(){
            $("input[name^='checkRow'").attr('checked',this.checked);
        });
        
        $("#button_file_import").on('click', function(e) {
            e.preventDefault();
            $(".se-pre-con").fadeIn();
            $("#submit_file_import").click();
        });
        
        $("#button_file_upload").on("click", function(e) {
            e.preventDefault();
            $(".se-pre-con").fadeIn();
            $("#submit_file_upload").click();
        });
    });
</script>