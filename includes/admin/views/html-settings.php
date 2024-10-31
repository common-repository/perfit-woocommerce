<br class="clear">
<h2><?php _e('Admin Settings Perfit Title', 'woocommerce-perfit'); ?></h2>
<p>
    <?php if(!empty($apikeyMask)){?>
    <?php _e('Admin Settings Perfit Activated Description', 'woocommerce-perfit'); ?>
    <?php }else{ ?>
    <?php _e('Admin Settings Perfit Description', 'woocommerce-perfit'); ?>
    <?php } ?>
</p>
<table class="form-table">
    <tbody>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label><?php _e('Admin Settings Perfit Api Key Field Label', 'woocommerce-perfit'); ?></label>
            </th>
            <td class="forminp forminp-text">
                <?php if(!empty($apikeyMask)){?>
                    <span><?php echo $apikeyMask;?></span>
                <?php }else{ ?>
                    <input type="password" id="apikey" name="apikey" value="<?=$apikey;?>" placeholder="<?php _e('Admin Settings Perfit Api Key Field Placeholder', 'woocommerce-perfit'); ?>" maxlength="255" required="required" autocomplete="off" />
                <?php } ?>
            </td>
        </tr>
    </tbody>
</table>
<?php if (!empty($apikey)) { ?>
<style type="text/css">
    .woocommerce-save-button{display: none !important;}
</style>
<p class="submit"><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=perfit&action=logout'); ?>" style="color: red;"><?php _e('Admin Settings Perfit Button Logout', 'woocommerce-perfit'); ?></a></p>
<?php } ?>