<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AgroManager_Weather {

    public function get_weather( $lat = null, $lng = null ) {
        if ( ! $lat ) $lat = get_option( 'agromanager_default_lat', '47.4979' );
        if ( ! $lng ) $lng = get_option( 'agromanager_default_lng', '19.0402' );

        $cache_key = 'agro_weather_' . md5( $lat . '_' . $lng );
        $cached = get_transient( $cache_key );
        if ( $cached !== false ) return $cached;

        $url = sprintf(
            'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&current=temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum&timezone=Europe%%2FBudapest&forecast_days=7',
            $lat, $lng
        );

        $response = wp_remote_get( $url, array( 'timeout' => 10 ) );
        if ( is_wp_error( $response ) ) return null;

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( empty( $data ) || isset( $data['error'] ) ) return null;

        set_transient( $cache_key, $data, HOUR_IN_SECONDS );
        return $data;
    }

    public function get_weather_icon( $code ) {
        $icons = array(
            0 => '☀️', 1 => '🌤️', 2 => '⛅', 3 => '☁️',
            45 => '🌫️', 48 => '🌫️',
            51 => '🌦️', 53 => '🌦️', 55 => '🌧️',
            61 => '🌧️', 63 => '🌧️', 65 => '🌧️',
            71 => '🌨️', 73 => '🌨️', 75 => '❄️',
            80 => '🌦️', 81 => '🌧️', 82 => '⛈️',
            95 => '⛈️', 96 => '⛈️', 99 => '⛈️',
        );
        return $icons[ $code ] ?? '🌡️';
    }

    public function get_weather_text( $code ) {
        $texts = array(
            0 => 'Derült', 1 => 'Többnyire derült', 2 => 'Részben felhős', 3 => 'Borult',
            45 => 'Ködös', 48 => 'Zúzmarás köd',
            51 => 'Enyhe szitálás', 53 => 'Szitálás', 55 => 'Erős szitálás',
            61 => 'Enyhe eső', 63 => 'Eső', 65 => 'Erős eső',
            71 => 'Enyhe havazás', 73 => 'Havazás', 75 => 'Erős havazás',
            80 => 'Záporok', 81 => 'Erős záporok', 82 => 'Felhőszakadás',
            95 => 'Zivatar', 96 => 'Jégesővel kísért zivatar', 99 => 'Erős jégeső',
        );
        return $texts[ $code ] ?? 'Ismeretlen';
    }

    public function render_weather_page() {
        $parcels_module = new AgroManager_Parcels();
        $parcels = $parcels_module->get_all( array( 'status' => 'active' ) );

        $lat = isset( $_GET['lat'] ) ? floatval( $_GET['lat'] ) : null;
        $lng = isset( $_GET['lng'] ) ? floatval( $_GET['lng'] ) : null;
        $selected_name = get_option( 'agromanager_default_location', 'Budapest' );

        if ( $lat && $lng && isset( $_GET['name'] ) ) {
            $selected_name = sanitize_text_field( $_GET['name'] );
        }

        $weather = $this->get_weather( $lat, $lng );
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1>🌤️ Időjárás</h1>
            </div>

            <?php if ( ! empty( $parcels ) ) : ?>
            <div class="agromanager-filter-bar">
                <form method="get">
                    <input type="hidden" name="page" value="agromanager-weather">
                    <select name="parcel_select" id="parcel_select" class="agro-input" onchange="var o=this.options[this.selectedIndex];document.getElementById('lat_input').value=o.dataset.lat;document.getElementById('lng_input').value=o.dataset.lng;document.getElementById('name_input').value=o.dataset.name;">
                        <option value="">Alapértelmezett helyszín</option>
                        <?php foreach ( $parcels as $p ) : if ( $p->gps_lat && $p->gps_lng ) : ?>
                            <option data-lat="<?php echo esc_attr($p->gps_lat); ?>" data-lng="<?php echo esc_attr($p->gps_lng); ?>" data-name="<?php echo esc_attr($p->name); ?>"
                                <?php if($lat == $p->gps_lat && $lng == $p->gps_lng) echo 'selected'; ?>>
                                <?php echo esc_html($p->name . ' (' . $p->location . ')'); ?>
                            </option>
                        <?php endif; endforeach; ?>
                    </select>
                    <input type="hidden" name="lat" id="lat_input" value="<?php echo esc_attr($lat ?? ''); ?>">
                    <input type="hidden" name="lng" id="lng_input" value="<?php echo esc_attr($lng ?? ''); ?>">
                    <input type="hidden" name="name" id="name_input" value="<?php echo esc_attr($selected_name); ?>">
                    <button type="submit" class="agro-btn agro-btn-secondary">Lekérdezés</button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ( ! $weather ) : ?>
                <div class="agromanager-empty-state">
                    <span style="font-size:48px;">🌍</span>
                    <h3>Nem sikerült lekérni az időjárási adatokat</h3>
                    <p>Ellenőrizd az internetkapcsolatot, vagy próbáld újra később.</p>
                </div>
            <?php else : ?>
                <div class="agro-weather-current">
                    <div class="agro-weather-main">
                        <span class="agro-weather-icon"><?php echo $this->get_weather_icon( $weather['current']['weather_code'] ); ?></span>
                        <div class="agro-weather-temp"><?php echo round($weather['current']['temperature_2m']); ?>°C</div>
                        <div class="agro-weather-desc"><?php echo esc_html($this->get_weather_text($weather['current']['weather_code'])); ?></div>
                        <div class="agro-weather-location">📍 <?php echo esc_html($selected_name); ?></div>
                    </div>
                    <div class="agro-weather-details">
                        <div class="agro-weather-detail"><span>💧 Páratartalom</span><strong><?php echo round($weather['current']['relative_humidity_2m']); ?>%</strong></div>
                        <div class="agro-weather-detail"><span>🌧️ Csapadék</span><strong><?php echo $weather['current']['precipitation']; ?> mm</strong></div>
                        <div class="agro-weather-detail"><span>💨 Szél</span><strong><?php echo round($weather['current']['wind_speed_10m']); ?> km/h</strong></div>
                    </div>
                </div>

                <h2 style="margin:30px 0 15px;">7 napos előrejelzés</h2>
                <div class="agro-weather-forecast">
                    <?php for ( $i = 0; $i < 7; $i++ ) :
                        $day_names = array('Vasárnap','Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat');
                        $date = strtotime($weather['daily']['time'][$i]);
                        $day = $day_names[date('w', $date)];
                        if ($i === 0) $day = 'Ma';
                        if ($i === 1) $day = 'Holnap';
                    ?>
                        <div class="agro-forecast-day">
                            <div class="agro-forecast-name"><?php echo esc_html($day); ?></div>
                            <div class="agro-forecast-date"><?php echo date_i18n('m.d', $date); ?></div>
                            <div class="agro-forecast-icon"><?php echo $this->get_weather_icon($weather['daily']['weather_code'][$i]); ?></div>
                            <div class="agro-forecast-temps">
                                <span class="agro-temp-high"><?php echo round($weather['daily']['temperature_2m_max'][$i]); ?>°</span>
                                <span class="agro-temp-low"><?php echo round($weather['daily']['temperature_2m_min'][$i]); ?>°</span>
                            </div>
                            <div class="agro-forecast-rain">🌧️ <?php echo $weather['daily']['precipitation_sum'][$i]; ?> mm</div>
                        </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_dashboard_widget() {
        $weather = $this->get_weather();
        if ( ! $weather ) { echo '<p>Időjárás nem elérhető.</p>'; return; }
        $loc = get_option('agromanager_default_location', 'Budapest');
        ?>
        <div class="agro-widget-weather">
            <span class="agro-weather-icon-sm"><?php echo $this->get_weather_icon($weather['current']['weather_code']); ?></span>
            <strong><?php echo round($weather['current']['temperature_2m']); ?>°C</strong>
            <span><?php echo esc_html($this->get_weather_text($weather['current']['weather_code'])); ?></span>
            <span class="agro-weather-loc">📍 <?php echo esc_html($loc); ?></span>
        </div>
        <?php
    }
}
