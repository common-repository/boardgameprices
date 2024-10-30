<?php
/**
 * @package boardgameprices
 * @version 1.1.4
 */
/*
Plugin Name: BoardGamePrices
Plugin URI: https://boardgameprices.eu/api/plugin
Description: Short code for embedding the best price for board games
Author: Kean Pedersen
Version: 1.1.5
Author URI: https://boardgameprices.eu
*/


function boardgameprice_host($country) 
{
    switch ($country) {
        case 'GB': return 'boardgameprices.co.uk';
        case 'DE': return 'brettspielpreise.de';
        case 'DK': return 'braetspilspriser.dk';
        case 'SE': return 'bradspelspriser.se';
        case 'US': return 'tabletopprices.com';
        case 'NL': return 'bordspelprijzen.nl';
        case 'FR': return 'ludiprix.fr';
        default: return 'boardgameprices.co.uk';
    }
}

function boardgameprices_api($atts)
{
    $param = shortcode_atts(array(
        'id' => null,
        'currency' => get_option('boardgamepricesccy','GBP'),
        'destination' => get_option('boardgamepricescountry', 'GB'),
        'delivery' => 'PACKAGE,POSTOFFICE',
        'sort' => get_option('boardgamepricessort', 'SMART'),
        ), $atts);
	
    $html = "";

    if (!$param['id']) {
        return __("[ID is required]", 'boardgameprices');
    }
	
    $args = array(
        'id' => $param['id'],
        'currency' => $param['currency'],
        'destination' => $param['destination'],
        'delivery' => $param['delivery'],
        'sort' => $param['sort'],
        'sitename' => home_url(),
    );

    $url = 'https://' . boardgameprice_host($param['destination']) . '/api/info' . '?' . http_build_query($args);
    /* Check cache */
    $data = wp_cache_get($url, 'boardgameprices');
    if ($data === false) {
        $response = wp_remote_get($url);
        $body = wp_remote_retrieve_body($response);
        if (!$body) {
            return __("[Error getting price]", 'boardgameprices');
        }
        $data = json_decode($body);
        wp_cache_set($url, $data, 'boardgameprices', 3600);
    }

    return $data;
}


function boardgameprice_boxshortcode( $atts = [], $content = null, $tag = '' )
{
    wp_enqueue_style('boardgameprices-style');
    $data = boardgameprices_api($atts);

    $out = '<table class="boardgamepricesinfobox">';
    $out .= '<tr><th colspan="2">';
    $out .= sprintf(__('Prices delivered by %s', 'boardgameprices'), '<a href="'.$data->url.'">BoardGamePrices</a>');
    $out .= '</th></tr>';

    foreach ($data->items as $item) {
        $out .= '<tr><td class="boardgamepricesimg"><img src="' . $item->thumbnail 
             . '" alt="' . htmlspecialchars($item->name) . '" /></td>';
        $out .= '<td><span class="boardgamepricesitemname">' . htmlspecialchars($item->name) . '</span><br />';
        if (count($item->prices) > 0) {
            $out .= boardgameprices_formatprice($item->prices[0]->price, $data->currency) . ' <em>';
            $out .= __('with shipping', 'boardgameprices');
            if ($item->prices[0]->stock == 'Y') {
                $out .= __(', in stock!', 'boardgameprices');
            } else {
                $out .= "!";
            }

            $itemurl = $item->url;
            $affiliateID = get_option('boardgamepricesaffiliateid', '');
            error_log($affiliateID);
            if ($affiliateID) {
                $query = parse_url($itemurl, PHP_URL_QUERY);
                if ($query) {
                    $itemurl .= '&aid=' . $affiliateID;
                } else {
                    $itemurl .= '?aid=' . $affiliateID;
                }
            }

            $out .= '</em><br /><a class="outlink" href="' . $itemurl . '">';
            $out .= __('Buy now','boardgameprices');
            $out .= '</a><br />';

            if (count($item->prices) > 2) {
                $out .= '<em><a href="'.$itemurl.'">';
                $out .= sprintf(__('See all %d offers!', 'boardgameprices'), count($item->prices));
                $out .= '</a></em>';
            }
        } else {
            $out .= "<em>";
            $out .= sprintf(__('%s could not find a price for this item.', 'boardgameprices'), $data->sitename);
            $out .= "<br />";
            $out .= '<a href="'.$item->url.'">';
            $out .= __('More details here', 'boardgameprices');
            $out .= '</a>.</em>';
        }



        $out .= '</td></tr>';
    }


    $out .= '</table>';

    return $out;

}

function boardgameprice_shortcode( $atts = [], $content = null, $tag = '' )
{
    $data = boardgameprices_api($atts);

    /* Build output */
    if (count($data->items) > 0 && count($data->items[0]->prices) > 0) {

        $itemurl = $data->items[0]->url;
        $affiliateID = get_option('boardgamepricesaffiliateid', '');
        if ($affiliateID) {
            $query = parse_url($itemurl, PHP_URL_QUERY);
            if ($query) {
                $itemurl .= '&aid=' . $affiliateID;
            } else {
                $itemurl .= '?aid=' . $affiliateID;
            }
        }

        $price = $data->items[0]->prices[0];
        $html  = '<a href="' . $itemurl . '" target="_blank">';
        $html .= boardgameprices_formatprice($price->price, $data->currency);
        $html .= '</a>';
    } else {
        return __('[unknown price]', 'boardgameprices');
    }

    return $html;
	
}

function boardgameprices_formatprice($amount, $currency)
{
    $html = "";
    switch ($currency) {
        case 'USD':
            $html .= '$' . number_format_i18n($amount,2);
            break;
        case 'SEK':
        case 'DKK':
            $html .= number_format_i18n($amount,2) . '&nbsp;kr';
            break;
        case 'EUR':
            $html .= '€' . number_format_i18n($amount,2);
            break;
        case 'GBP':
            $html .= '£' . number_format_i18n($amount,2);
            break;
        default:
            $html .= number_format_i18n($amount,2);
            break;
    }
    return $html;

}

function boardgameprices_setting()
{
    register_setting('boardgameprices_options', 'boardgamepricescountry');
    register_setting('boardgameprices_options', 'boardgamepricesccy');
    register_setting('boardgameprices_options', 'boardgamepricessort');
    register_setting('boardgameprices_options', 'boardgamepricesaffiliateid');
}

function boardgameprices_menu()
{
    add_options_page(__('BoardGamePrices Settings','boardgameprices'), 
                     'BoardGamePrices', 
                     'manage_options', 
                     'boardgameprices',
                     'boardgameprices_option');
}

function boardgameprices_option()
{
?>
<div class="wrap">
    <h2><?php _e('BoardGamePrices settings','boardgameprices') ?></h2>
    <form method="POST" action="options.php">
<?php
 settings_fields('boardgameprices_options');
?><table>
   <tr>
    <td><strong><?php _e('Destination country for shipping prices:','boardgameprices') ?></strong></td>
    <td><select name="boardgamepricescountry">
         <option value="GB"<?php selected(get_option('boardgamepricescountry'),'GB'); ?>><?php _e('United Kingdom', 'boardgameprices') ?></option>
         <option value="DK"<?php selected(get_option('boardgamepricescountry'),'DK'); ?>><?php _e('Denmark', 'boardgameprices') ?></option>
         <option value="SE"<?php selected(get_option('boardgamepricescountry'),'SE'); ?>><?php _e('Sweden', 'boardgameprices') ?></option>
         <option value="DE"<?php selected(get_option('boardgamepricescountry'),'DE'); ?>><?php _e('Germany', 'boardgameprices') ?></option>
         <option value="US"<?php selected(get_option('boardgamepricescountry'),'US'); ?>><?php _e('United States', 'boardgameprices') ?></option>
         <option value="NL"<?php selected(get_option('boardgamepricescountry'),'NL'); ?>><?php _e('The Netherlands', 'boardgameprices') ?></option>
         <option value="FR"<?php selected(get_option('boardgamepricescountry'),'FR'); ?>><?php _e('France', 'boardgameprices') ?></option>
         <option value="BE"<?php selected(get_option('boardgamepricescountry'),'BE'); ?>><?php _e('Belgium', 'boardgameprices') ?></option>
         <option value="ES"<?php selected(get_option('boardgamepricescountry'),'ES'); ?>><?php _e('Spain', 'boardgameprices') ?></option>
         <option value="FR"<?php selected(get_option('boardgamepricescountry'),'FR'); ?>><?php _e('France', 'boardgameprices') ?></option>
         <option value="PL"<?php selected(get_option('boardgamepricescountry'),'PL'); ?>><?php _e('Poland', 'boardgameprices') ?></option>
         <option value="IE"<?php selected(get_option('boardgamepricescountry'),'IE'); ?>><?php _e('Ireland', 'boardgameprices') ?></option>
         <option value="IT"<?php selected(get_option('boardgamepricescountry'),'IT'); ?>><?php _e('Italy', 'boardgameprices') ?></option>
         <option value="FI"<?php selected(get_option('boardgamepricescountry'),'FI'); ?>><?php _e('Finland', 'boardgameprices') ?></option>
         <option value="NO"<?php selected(get_option('boardgamepricescountry'),'NO'); ?>><?php _e('Norway', 'boardgameprices') ?></option>
         <option value="PT"<?php selected(get_option('boardgamepricescountry'),'PT'); ?>><?php _e('Portugal', 'boardgameprices') ?></option>
         <option value="GR"<?php selected(get_option('boardgamepricescountry'),'GR'); ?>><?php _e('Greece', 'boardgameprices') ?></option>
         <option value="AT"<?php selected(get_option('boardgamepricescountry'),'AT'); ?>><?php _e('Switzerland', 'boardgameprices') ?></option>
        </select></td>
   </tr>
   <tr>
    <td><strong><?php _e('Currency:','boardgameprices') ?></strong></td>
    <td><select name="boardgamepricesccy">
         <option value="GBP"<?php selected(get_option('boardgamepricesccy'),'GBP'); ?>>GBP</option>
         <option value="DKK"<?php selected(get_option('boardgamepricesccy'),'DKK'); ?>>DKK</option>
         <option value="SEK"<?php selected(get_option('boardgamepricesccy'),'SEK'); ?>>SEK</option>
         <option value="EUR"<?php selected(get_option('boardgamepricesccy'),'EUR'); ?>>EUR</option>
         <option value="USD"<?php selected(get_option('boardgamepricesccy'),'USD'); ?>>USD</option>
        </select></td>
   </tr>
   <tr>
    <td><strong><?php _e('Preference for price selection:','boardgameprices') ?></strong></td>
    <td><input type="radio" name="boardgamepricessort" value="SMART"<?php checked(get_option('boardgamepricessort'),'SMART'); ?>>
         <?php _e('Prefer items in stock from local stores (recommended).', 'boardgameprices') ?><br />
        <input type="radio" name="boardgamepricessort" value="STOCK"<?php checked(get_option('boardgamepricessort'),'STOCK'); ?>>
         <?php _e('Prefer items in stock (from all stores).', 'boardgameprices') ?><br />
        <input type="radio" name="boardgamepricessort" value="CHEAP1"<?php checked(get_option('boardgamepricessort'),'CHEAP1'); ?>>
         <?php _e('Always select the cheapest price (with shipping).', 'boardgameprices') ?></td>
   </tr>
   <tr>
       <td><strong><?php _e('Affiliate ID:', 'boardgameprices') ?></strong></td>
       <td><input type="text" name="boardgamepricesaffiliateid" value="<?php echo esc_attr(get_option('boardgamepricesaffiliateid'),''); ?>"></td>
   </tr>
   <tr> 
    <td>&nbsp;</td>
    <td><input type="submit" class="button-primary" value="<?php _e('Save Changes', 'boardgameprices') ?>" /></td>
   </tr>
 </table>
</form>
</div>
<?php
}

function boardgameprices_stylesheet() 
{
    wp_register_style('boardgameprices-style', plugins_url('bgp.css', __FILE__));
}

function boardgameprices_activate()
{
    add_option('boardgamepricescountry','GB');
    add_option('boardgamepricesccy', 'GBP');
    add_option('boardgamepricessort', 'SMART');
    add_option('boardgamepricesaffiliateid', '');
}

function boardgameprices_deactivate()
{   
    delete_option('boardgamepricescountry');
    delete_option('boardgamepricesccy');
    delete_option('boardgamepricessort');
    delete_option('boardgamepricesaffiliateid');
}

function boardgameprices_pluginsloaded()
{
    load_plugin_textdomain('boardgameprices', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

add_action('init', 'boardgameprices_init');

function boardgameprices_init()
{
    add_action('admin_init', 'boardgameprices_setting');
    add_action('admin_menu', 'boardgameprices_menu');
    add_shortcode('boardgameprice', 'boardgameprice_shortcode');
    add_shortcode('boardgamepricebox', 'boardgameprice_boxshortcode');
    add_action('wp_enqueue_scripts', 'boardgameprices_stylesheet');
    add_action('plugins_loaded', 'boardgameprices_pluginsloaded');
    register_activation_hook(__FILE__, 'boardgameprices_activate');
    register_deactivation_hook(__FILE__, 'boardgameprices_deactivate');
}
