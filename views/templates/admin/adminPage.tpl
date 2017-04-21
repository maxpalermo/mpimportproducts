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
            <button type="submit" value="1" id="submit_file_upload" name="submit_file_upload" class="btn btn-default pull-right">
                <i class="icon-2x icon-upload-alt"></i> 
                {l s='Upload Excel' mod='mpimportproducts'}
            </button>
        </div>
    </div>
    <!-- TABLE SECTION -->
    <div class='panel'>
        <div class='panel-heading'>
            <span>
                <i class='icon-table'></i>
                {l s='Invoices List' mod='mpimportproducts'}
            </span>
        </div>
        <div class='panel-footer'>
            <button type="submit" value="1" id="submit_file_import" name="submit_file_import" class="btn btn-default pull-right">
                <i class="icon-download"></i> 
                {l s='Import products' mod='mpimportproducts'}
            </button>
        </div>
        <div class='panel-body'>
            
        </div>
        <div class='panel-footer'>
            <button type="submit" value="1" id="submit_file_import" name="submit_file_import" class="btn btn-default pull-right">
                <i class="icon-download"></i> 
                {l s='Import products' mod='mpimportproducts'}
            </button>
        </div>
    </div>
</form>

            <pre>
                {$file|print_r}
                
                EXCEL:
                {$rows|print_r}
            </pre>
            
<script type="text/javascript">
    $(window).bind("load",function()
    {    
        $("#checkAll").on("change",function(){
            $("input[name^='checkRow'").attr('checked',this.checked);
        });
    });
</script>