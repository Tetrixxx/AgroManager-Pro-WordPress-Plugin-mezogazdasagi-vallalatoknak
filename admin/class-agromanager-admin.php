<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AgroManager_Admin {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_menu_page( 'AgroManager Pro', '🌾 AgroManager', 'manage_options', 'agromanager', array( $this, 'render_dashboard' ), '', 26 );
        add_submenu_page( 'agromanager', 'Dashboard', '📊 Dashboard', 'manage_options', 'agromanager', array( $this, 'render_dashboard' ) );
        add_submenu_page( 'agromanager', 'Parcellák', '🗺️ Parcellák', 'manage_options', 'agromanager-parcels', array( $this, 'render_parcels' ) );
        add_submenu_page( 'agromanager', 'Kultúrák', '🌱 Kultúrák', 'manage_options', 'agromanager-crops', array( $this, 'render_crops' ) );
        add_submenu_page( 'agromanager', 'Géppark', '🚜 Géppark', 'manage_options', 'agromanager-machines', array( $this, 'render_machines' ) );
        add_submenu_page( 'agromanager', 'Időjárás', '🌤️ Időjárás', 'manage_options', 'agromanager-weather', array( $this, 'render_weather' ) );
        add_submenu_page( 'agromanager', 'Pénzügyek', '💰 Pénzügyek', 'manage_options', 'agromanager-finances', array( $this, 'render_finances' ) );
        add_submenu_page( 'agromanager', 'Dolgozók', '👷 Dolgozók', 'manage_options', 'agromanager-workers', array( $this, 'render_workers' ) );
        add_submenu_page( 'agromanager', 'Beállítások', '⚙️ Beállítások', 'manage_options', 'agromanager-settings', array( $this, 'render_settings' ) );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'agromanager' ) === false ) return;
        wp_enqueue_style( 'agromanager-admin', AGROMANAGER_PLUGIN_URL . 'admin/css/agromanager-admin.css', array(), AGROMANAGER_VERSION );
        wp_enqueue_script( 'agromanager-chart', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true );
        wp_enqueue_script( 'agromanager-admin', AGROMANAGER_PLUGIN_URL . 'admin/js/agromanager-admin.js', array( 'jquery', 'agromanager-chart' ), AGROMANAGER_VERSION, true );
        wp_localize_script( 'agromanager-admin', 'agroData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'agro_ajax_nonce' ),
        ) );
    }

    public function register_settings() {
        register_setting( 'agromanager_settings', 'agromanager_currency' );
        register_setting( 'agromanager_settings', 'agromanager_area_unit' );
        register_setting( 'agromanager_settings', 'agromanager_default_location' );
        register_setting( 'agromanager_settings', 'agromanager_default_lat' );
        register_setting( 'agromanager_settings', 'agromanager_default_lng' );
    }

    public function render_dashboard() {
        $parcels = new AgroManager_Parcels();
        $crops = new AgroManager_Crops();
        $machines = new AgroManager_Machines();
        $finances = new AgroManager_Finances();
        $workers = new AgroManager_Workers();
        $weather = new AgroManager_Weather();

        $total_ha = $parcels->total_hectares();
        $parcel_count = $parcels->count();
        $crop_count = $crops->count();
        $machine_count = $machines->count();
        $worker_count = $workers->count('active');
        $financial = $finances->get_summary();
        $machines_needing_service = $machines->get_needing_service();
        $monthly = $finances->get_monthly_summary();
        $currency = get_option('agromanager_currency','HUF');

        // Prepare chart data
        $chart_income = array_fill(0,12,0);
        $chart_expense = array_fill(0,12,0);
        foreach ($monthly as $m) {
            $idx = intval($m->month) - 1;
            if ($m->type === 'income') $chart_income[$idx] = floatval($m->total);
            else $chart_expense[$idx] = floatval($m->total);
        }
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header agromanager-dashboard-header">
                <h1>🌾 AgroManager Pro – Áttekintés</h1>
                <span class="agro-date"><?php echo date_i18n('Y. F j., l'); ?></span>
            </div>

            <div class="agro-dashboard-grid">
                <!-- Summary cards -->
                <div class="agro-dash-card agro-card-green">
                    <div class="agro-dash-icon">🗺️</div>
                    <div class="agro-dash-info">
                        <div class="agro-dash-value"><?php echo number_format($total_ha,0,',',' '); ?> ha</div>
                        <div class="agro-dash-label"><?php echo $parcel_count; ?> parcella</div>
                    </div>
                </div>
                <div class="agro-dash-card agro-card-emerald">
                    <div class="agro-dash-icon">🌱</div>
                    <div class="agro-dash-info">
                        <div class="agro-dash-value"><?php echo $crop_count; ?></div>
                        <div class="agro-dash-label">Kultúra</div>
                    </div>
                </div>
                <div class="agro-dash-card agro-card-amber">
                    <div class="agro-dash-icon">🚜</div>
                    <div class="agro-dash-info">
                        <div class="agro-dash-value"><?php echo $machine_count; ?></div>
                        <div class="agro-dash-label">Gép</div>
                    </div>
                </div>
                <div class="agro-dash-card agro-card-blue">
                    <div class="agro-dash-icon">👷</div>
                    <div class="agro-dash-info">
                        <div class="agro-dash-value"><?php echo $worker_count; ?></div>
                        <div class="agro-dash-label">Aktív dolgozó</div>
                    </div>
                </div>

                <!-- Financial summary -->
                <div class="agro-dash-card agro-card-wide agro-card-finance">
                    <h3>💰 Pénzügyi összesítő – <?php echo date('Y'); ?></h3>
                    <div class="agro-finance-summary">
                        <div class="agro-fin-item agro-fin-income">
                            <span>Bevételek</span>
                            <strong><?php echo number_format($financial['income'],0,',',' '); ?> <?php echo esc_html($currency); ?></strong>
                        </div>
                        <div class="agro-fin-item agro-fin-expense">
                            <span>Kiadások</span>
                            <strong><?php echo number_format($financial['expense'],0,',',' '); ?> <?php echo esc_html($currency); ?></strong>
                        </div>
                        <div class="agro-fin-item <?php echo $financial['profit']>=0?'agro-fin-profit':'agro-fin-loss'; ?>">
                            <span>Eredmény</span>
                            <strong><?php echo ($financial['profit']>=0?'+':'').number_format($financial['profit'],0,',',' '); ?> <?php echo esc_html($currency); ?></strong>
                        </div>
                    </div>
                    <canvas id="agro-finance-chart" height="200"></canvas>
                </div>

                <!-- Weather widget -->
                <div class="agro-dash-card agro-card-weather">
                    <h3>🌤️ Időjárás</h3>
                    <?php $weather->render_dashboard_widget(); ?>
                </div>

                <!-- Service alerts -->
                <?php if ( ! empty( $machines_needing_service ) ) : ?>
                <div class="agro-dash-card agro-card-alert">
                    <h3>🔧 Közelgő szervizek</h3>
                    <ul class="agro-service-list">
                        <?php foreach ( array_slice($machines_needing_service, 0, 5) as $m ) : ?>
                            <li>
                                <strong><?php echo esc_html($m->name); ?></strong>
                                <span><?php echo esc_html(date_i18n('Y.m.d',strtotime($m->next_service))); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('agro-finance-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Jan','Feb','Már','Ápr','Máj','Jún','Júl','Aug','Szep','Okt','Nov','Dec'],
                        datasets: [
                            { label: 'Bevétel', data: <?php echo json_encode($chart_income); ?>, backgroundColor: 'rgba(34,197,94,0.7)', borderRadius: 6 },
                            { label: 'Kiadás', data: <?php echo json_encode($chart_expense); ?>, backgroundColor: 'rgba(239,68,68,0.7)', borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return v.toLocaleString('hu-HU') + ' <?php echo esc_js($currency); ?>'; } } } }
                    }
                });
            }
        });
        </script>
        <?php
    }

    public function render_parcels() {
        $module = new AgroManager_Parcels();
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        if (in_array($action, array('add','edit'))) { $module->render_form_page(); } else { $module->render_list_page(); }
    }

    public function render_crops() {
        $module = new AgroManager_Crops();
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        if (in_array($action, array('add','edit'))) { $module->render_form_page(); } else { $module->render_list_page(); }
    }

    public function render_machines() {
        $module = new AgroManager_Machines();
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        if (in_array($action, array('add','edit'))) { $module->render_form_page(); } else { $module->render_list_page(); }
    }

    public function render_weather() {
        $module = new AgroManager_Weather();
        $module->render_weather_page();
    }

    public function render_finances() {
        $module = new AgroManager_Finances();
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        if (in_array($action, array('add','edit'))) { $module->render_form_page(); } else { $module->render_list_page(); }
    }

    public function render_workers() {
        $module = new AgroManager_Workers();
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        if ($action === 'worklog') { $module->render_worklog_page(); }
        elseif (in_array($action, array('add','edit'))) { $module->render_form_page(); }
        else { $module->render_list_page(); }
    }

    public function render_settings() {
        if ( isset($_POST['agromanager_settings_nonce']) && wp_verify_nonce($_POST['agromanager_settings_nonce'],'agromanager_save_settings') ) {
            update_option('agromanager_currency', sanitize_text_field($_POST['agromanager_currency']??'HUF'));
            update_option('agromanager_area_unit', sanitize_text_field($_POST['agromanager_area_unit']??'ha'));
            update_option('agromanager_default_location', sanitize_text_field($_POST['agromanager_default_location']??''));
            update_option('agromanager_default_lat', sanitize_text_field($_POST['agromanager_default_lat']??''));
            update_option('agromanager_default_lng', sanitize_text_field($_POST['agromanager_default_lng']??''));
            echo '<div class="notice notice-success"><p>Beállítások mentve.</p></div>';
        }
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header"><h1>⚙️ Beállítások</h1></div>
            <form method="post" class="agromanager-form">
                <?php wp_nonce_field('agromanager_save_settings','agromanager_settings_nonce'); ?>
                <div class="agro-form-grid">
                    <div class="agro-form-group">
                        <label for="agromanager_currency">Pénznem</label>
                        <select id="agromanager_currency" name="agromanager_currency" class="agro-input">
                            <?php $cur = get_option('agromanager_currency','HUF'); foreach(array('HUF'=>'HUF (Ft)','EUR'=>'EUR (€)','USD'=>'USD ($)') as $v=>$l): ?>
                            <option value="<?php echo esc_attr($v);?>" <?php selected($cur,$v);?>><?php echo esc_html($l);?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="agro-form-group">
                        <label for="agromanager_area_unit">Területi egység</label>
                        <select id="agromanager_area_unit" name="agromanager_area_unit" class="agro-input">
                            <?php $unit = get_option('agromanager_area_unit','ha'); ?>
                            <option value="ha" <?php selected($unit,'ha');?>>Hektár (ha)</option>
                            <option value="kh" <?php selected($unit,'kh');?>>Katasztrális hold (kh)</option>
                        </select>
                    </div>
                    <div class="agro-form-group"><label for="agromanager_default_location">Alapértelmezett helyszín</label><input type="text" id="agromanager_default_location" name="agromanager_default_location" value="<?php echo esc_attr(get_option('agromanager_default_location','Budapest, Magyarország')); ?>" class="agro-input"></div>
                    <div class="agro-form-group"><label for="agromanager_default_lat">GPS szélesség</label><input type="number" step="0.0000001" id="agromanager_default_lat" name="agromanager_default_lat" value="<?php echo esc_attr(get_option('agromanager_default_lat','47.4979')); ?>" class="agro-input"></div>
                    <div class="agro-form-group"><label for="agromanager_default_lng">GPS hosszúság</label><input type="number" step="0.0000001" id="agromanager_default_lng" name="agromanager_default_lng" value="<?php echo esc_attr(get_option('agromanager_default_lng','19.0402')); ?>" class="agro-input"></div>
                </div>
                <div class="agro-form-actions"><button type="submit" class="agro-btn agro-btn-primary agro-btn-lg"><span class="dashicons dashicons-saved"></span> Beállítások mentése</button></div>
            </form>
        </div>
        <?php
    }
}
