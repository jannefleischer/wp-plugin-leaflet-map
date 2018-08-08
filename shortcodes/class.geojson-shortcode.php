<?php
/**
 * GeoJSON Shortcode
 *
 * Use with [leaflet-geojson src="..."]
 * 
 * PHP Version 5.5
 * 
 * @category Shortcode
 * @author   Benjamin J DeLong <ben@bozdoz.com>
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once LEAFLET_MAP__PLUGIN_DIR . 'shortcodes/class.shortcode.php';

/**
 * GeoJSON Shortcode Class
 */
class Leaflet_Geojson_Shortcode extends Leaflet_Shortcode
{
    /**
     * Default src for geoJSON
     * 
     * @var string $default_src
     */
    public static $default_src = 'https://rawgit.com/bozdoz/567817310f102d169510d94306e4f464/raw/2fdb48dafafd4c8304ff051f49d9de03afb1718b/map.geojson';

    /**
     * How leaflet renders the src
     * 
     * @var string $type 
     */
    public static $type = 'json';

    /**
     * Get Script for Shortcode
     * 
     * @param string $atts    could be an array
     * @param string $content could be an array
     * 
     * @return string HTML
     */
    protected function getHTML($atts='', $content=null)
    {

        // need to get the called class to extend above variables
        $class = self::getClass();
        
        if ($atts) {
            extract($atts);
        } 

        wp_enqueue_script('leaflet_ajax_geojson_js');

        if ($content) {
            $content = str_replace(array("\r\n", "\n", "\r"), '<br>', $content);
            $content = htmlspecialchars($content);
        }

        /* only required field for geojson; accept either src or source */
        $source = empty($source) ? '' : $source;
        $src = empty($src) ? $class::$default_src : $src;
        $src = empty($source) ? $src : $source;

        $style_json = $this->LM->get_style_json($atts);

        $fitbounds = empty($fitbounds) ? 0 : $fitbounds;

        // shortcode content becomes popup text
        $content_text = empty($content) ? '' : $content;
        // alternatively, the popup_text attribute works as popup text
        $popup_text = empty($popup_text) ? '' : $popup_text;
        // choose which one takes priority (content_text)
        $popup_text = empty($content_text) ? $popup_text : $content_text;

        $popup_property = empty($popup_property) ? '' : $popup_property;

        $popup_text = trim($popup_text);

        ob_start();
        ?>
        <script>
            WPLeafletMapPlugin.add(function () {
                var previous_map = WPLeafletMapPlugin.getCurrentMap(),
                    src = '<?php echo $src; ?>',
                    default_style = <?php echo $style_json; ?>,
                    rewrite_keys = {
                        fill : 'fillColor',
                        'fill-opacity' : 'fillOpacity',
                        stroke : 'color',
                        'stroke-opacity' : 'opacity',
                        'stroke-width' : 'weight',
                    },
                    layer = L.ajaxGeoJson(src, {
                        type: '<?php echo $class::$type; ?>',
                        style : layerStyle,
                        onEachFeature : onEachFeature
                    }),
                    fitbounds = <?php echo $fitbounds; ?>,
                    popup_text = WPLeafletMapPlugin.unescape('<?php echo $popup_text; ?>'),
                    popup_property = '<?php echo $popup_property; ?>';
                if (fitbounds) {
                    layer.on('ready', function () {
                        this.map.fitBounds( this.getBounds() );
                    });
                }
                layer.addTo( previous_map );
                function layerStyle (feature) {
                    var props = feature.properties || {};
                    var style = {};
                    var camelFun = function camelFun (_, first_letter) {
                        return first_letter.toUpperCase();
                    };
                    for (var key in props) {
                        if (key.match('-')) {
                            var camelcase = key.replace(/-(\w)/, camelFun);
                            style[ camelcase ] = props[ key ];
                        }
                        // rewrite style keys from geojson.io
                        if (rewrite_keys[ key ]) {
                            style[ rewrite_keys[ key ] ] = props[ key ];
                        }
                    }
                    style = L.Util.extend(style, default_style);
                    return style;
                }      
                function onEachFeature (feature, layer) {
                    var props = feature.properties || {};
                    var text = popup_property && props[ popup_property ] || 
                        template(popup_text, feature.properties);
                    if (text) {
                        layer.bindPopup( text );
                    }
                }
                var templateRe = /\{ *([\w_-]+) *\}/g;
                function template(str, data) {
                    return str.replace(templateRe, function (match, key) {
                        var value = data[key];
                        if (value === undefined) {
                            return match;
                        }
                        return value;
                    });
                }  
            });
        </script>
        <?php
        return ob_get_clean();
    }
}