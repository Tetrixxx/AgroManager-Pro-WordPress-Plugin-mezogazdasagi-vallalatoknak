<?php
/**
 * AgroManager Pro – Parcels Module
 *
 * Manages land parcels: CRUD operations, listing, and form rendering.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AgroManager_Parcels {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'agro_parcels';
    }

    /**
     * Get all parcels with optional filtering.
     */
    public function get_all( $args = array() ) {
        global $wpdb;
        $defaults = array(
            'status'  => '',
            'search'  => '',
            'orderby' => 'name',
            'order'   => 'ASC',
        );
        $args = wp_parse_args( $args, $defaults );

        $where = '1=1';
        $params = array();

        if ( ! empty( $args['status'] ) ) {
            $where .= ' AND status = %s';
            $params[] = $args['status'];
        }
        if ( ! empty( $args['search'] ) ) {
            $where .= ' AND (name LIKE %s OR location LIKE %s)';
            $params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        }

        $allowed_orderby = array( 'name', 'size_ha', 'location', 'status', 'created_at' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'name';
        $order   = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY {$orderby} {$order}";

        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, $params );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get a single parcel by ID.
     */
    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            intval( $id )
        ) );
    }

    /**
     * Insert a new parcel.
     */
    public function insert( $data ) {
        global $wpdb;
        $sanitized = $this->sanitize_data( $data );
        $wpdb->insert( $this->table_name, $sanitized );
        return $wpdb->insert_id;
    }

    /**
     * Update a parcel.
     */
    public function update( $id, $data ) {
        global $wpdb;
        $sanitized = $this->sanitize_data( $data );
        return $wpdb->update(
            $this->table_name,
            $sanitized,
            array( 'id' => intval( $id ) )
        );
    }

    /**
     * Delete a parcel.
     */
    public function delete( $id ) {
        global $wpdb;
        return $wpdb->delete(
            $this->table_name,
            array( 'id' => intval( $id ) )
        );
    }

    /**
     * Count parcels by status.
     */
    public function count( $status = '' ) {
        global $wpdb;
        if ( ! empty( $status ) ) {
            return (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s",
                $status
            ) );
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
    }

    /**
     * Get total hectares.
     */
    public function total_hectares() {
        global $wpdb;
        return (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(size_ha), 0) FROM {$this->table_name} WHERE status = 'active'"
        );
    }

    /**
     * Get parcels for dropdown (id => name).
     */
    public function get_dropdown_options() {
        global $wpdb;
        $results = $wpdb->get_results(
            "SELECT id, name, size_ha FROM {$this->table_name} WHERE status = 'active' ORDER BY name ASC"
        );
        $options = array();
        foreach ( $results as $row ) {
            $options[ $row->id ] = sprintf( '%s (%s ha)', $row->name, $row->size_ha );
        }
        return $options;
    }

    /**
     * Sanitize parcel data.
     */
    private function sanitize_data( $data ) {
        return array(
            'name'             => sanitize_text_field( $data['name'] ?? '' ),
            'size_ha'          => floatval( $data['size_ha'] ?? 0 ),
            'location'         => sanitize_text_field( $data['location'] ?? '' ),
            'gps_lat'          => ! empty( $data['gps_lat'] ) ? floatval( $data['gps_lat'] ) : null,
            'gps_lng'          => ! empty( $data['gps_lng'] ) ? floatval( $data['gps_lng'] ) : null,
            'soil_quality'     => intval( $data['soil_quality'] ?? 5 ),
            'cultivation_type' => sanitize_text_field( $data['cultivation_type'] ?? '' ),
            'status'           => sanitize_text_field( $data['status'] ?? 'active' ),
            'notes'            => sanitize_textarea_field( $data['notes'] ?? '' ),
        );
    }

    /**
     * Render parcel list page.
     */
    public function render_list_page() {
        // Handle delete action
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            if ( check_admin_referer( 'agro_delete_parcel_' . $_GET['id'] ) ) {
                $this->delete( intval( $_GET['id'] ) );
                echo '<div class="notice notice-success"><p>Parcella sikeresen törölve.</p></div>';
            }
        }

        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $parcels = $this->get_all( array( 'search' => $search ) );

        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1>🌾 Parcellák</h1>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=agromanager-parcels&action=add' ) ); ?>" class="agro-btn agro-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span> Új parcella
                </a>
            </div>

            <div class="agromanager-filter-bar">
                <form method="get" action="">
                    <input type="hidden" name="page" value="agromanager-parcels">
                    <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Keresés név vagy helyszín alapján..." class="agro-search-input">
                    <button type="submit" class="agro-btn agro-btn-secondary">Keresés</button>
                </form>
            </div>

            <?php if ( empty( $parcels ) ) : ?>
                <div class="agromanager-empty-state">
                    <span class="dashicons dashicons-location-alt" style="font-size:48px;color:#94a3b8;"></span>
                    <h3>Még nincsenek parcellák</h3>
                    <p>Adj hozzá az első parcellát a nyilvántartáshoz!</p>
                </div>
            <?php else : ?>
                <div class="agromanager-table-wrap">
                    <table class="agromanager-table">
                        <thead>
                            <tr>
                                <th>Név</th>
                                <th>Méret (ha)</th>
                                <th>Helyszín</th>
                                <th>Talajminőség</th>
                                <th>Művelési ág</th>
                                <th>Státusz</th>
                                <th>Műveletek</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $parcels as $parcel ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $parcel->name ); ?></strong></td>
                                    <td><?php echo esc_html( number_format( $parcel->size_ha, 2, ',', ' ' ) ); ?></td>
                                    <td><?php echo esc_html( $parcel->location ); ?></td>
                                    <td>
                                        <div class="agro-quality-bar">
                                            <div class="agro-quality-fill" style="width:<?php echo intval( $parcel->soil_quality ) * 10; ?>%"></div>
                                            <span><?php echo intval( $parcel->soil_quality ); ?>/10</span>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html( $parcel->cultivation_type ); ?></td>
                                    <td>
                                        <span class="agro-badge agro-badge-<?php echo esc_attr( $parcel->status ); ?>">
                                            <?php echo esc_html( $this->get_status_label( $parcel->status ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=agromanager-parcels&action=edit&id=' . $parcel->id ) ); ?>" class="agro-btn agro-btn-sm agro-btn-edit" title="Szerkesztés">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=agromanager-parcels&action=delete&id=' . $parcel->id ), 'agro_delete_parcel_' . $parcel->id ); ?>" class="agro-btn agro-btn-sm agro-btn-delete" title="Törlés" onclick="return confirm('Biztosan törölni szeretnéd ezt a parcellát?');">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render add/edit form.
     */
    public function render_form_page() {
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $parcel = $id ? $this->get( $id ) : null;
        $is_edit = ! empty( $parcel );

        // Handle form submission
        if ( isset( $_POST['agro_parcel_nonce'] ) && wp_verify_nonce( $_POST['agro_parcel_nonce'], 'agro_save_parcel' ) ) {
            if ( $is_edit ) {
                $this->update( $id, $_POST );
                $parcel = $this->get( $id );
                echo '<div class="notice notice-success"><p>Parcella sikeresen frissítve.</p></div>';
            } else {
                $new_id = $this->insert( $_POST );
                if ( $new_id ) {
                    wp_redirect( admin_url( 'admin.php?page=agromanager-parcels&action=edit&id=' . $new_id . '&saved=1' ) );
                    exit;
                }
            }
        }

        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success"><p>Parcella sikeresen mentve.</p></div>';
        }

        $cultivation_types = array(
            'szántó'       => 'Szántó',
            'rét'          => 'Rét',
            'legelő'       => 'Legelő',
            'szőlő'        => 'Szőlő',
            'gyümölcsös'   => 'Gyümölcsös',
            'kert'         => 'Kert',
            'erdő'         => 'Erdő',
            'nádas'        => 'Nádas',
            'halastó'      => 'Halastó',
            'egyéb'        => 'Egyéb',
        );

        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1><?php echo $is_edit ? '✏️ Parcella szerkesztése' : '➕ Új parcella'; ?></h1>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=agromanager-parcels' ) ); ?>" class="agro-btn agro-btn-secondary">
                    ← Vissza a listához
                </a>
            </div>

            <form method="post" class="agromanager-form">
                <?php wp_nonce_field( 'agro_save_parcel', 'agro_parcel_nonce' ); ?>

                <div class="agro-form-grid">
                    <div class="agro-form-group">
                        <label for="name">Parcella neve *</label>
                        <input type="text" id="name" name="name" value="<?php echo esc_attr( $parcel->name ?? '' ); ?>" required class="agro-input">
                    </div>

                    <div class="agro-form-group">
                        <label for="size_ha">Méret (hektár) *</label>
                        <input type="number" id="size_ha" name="size_ha" value="<?php echo esc_attr( $parcel->size_ha ?? '' ); ?>" step="0.01" min="0" required class="agro-input">
                    </div>

                    <div class="agro-form-group">
                        <label for="location">Helyszín / Település</label>
                        <input type="text" id="location" name="location" value="<?php echo esc_attr( $parcel->location ?? '' ); ?>" class="agro-input">
                    </div>

                    <div class="agro-form-group">
                        <label for="cultivation_type">Művelési ág</label>
                        <select id="cultivation_type" name="cultivation_type" class="agro-input">
                            <option value="">– Válassz –</option>
                            <?php foreach ( $cultivation_types as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $parcel->cultivation_type ?? '', $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="agro-form-group">
                        <label for="gps_lat">GPS szélesség</label>
                        <input type="number" id="gps_lat" name="gps_lat" value="<?php echo esc_attr( $parcel->gps_lat ?? '' ); ?>" step="0.0000001" class="agro-input" placeholder="pl. 47.4979">
                    </div>

                    <div class="agro-form-group">
                        <label for="gps_lng">GPS hosszúság</label>
                        <input type="number" id="gps_lng" name="gps_lng" value="<?php echo esc_attr( $parcel->gps_lng ?? '' ); ?>" step="0.0000001" class="agro-input" placeholder="pl. 19.0402">
                    </div>

                    <div class="agro-form-group">
                        <label for="soil_quality">Talajminőség (1-10)</label>
                        <input type="range" id="soil_quality" name="soil_quality" value="<?php echo esc_attr( $parcel->soil_quality ?? 5 ); ?>" min="1" max="10" class="agro-range">
                        <span id="soil_quality_display" class="agro-range-value"><?php echo intval( $parcel->soil_quality ?? 5 ); ?></span>
                    </div>

                    <div class="agro-form-group">
                        <label for="status">Státusz</label>
                        <select id="status" name="status" class="agro-input">
                            <option value="active" <?php selected( $parcel->status ?? 'active', 'active' ); ?>>Aktív</option>
                            <option value="fallow" <?php selected( $parcel->status ?? '', 'fallow' ); ?>>Pihentetett</option>
                            <option value="rented" <?php selected( $parcel->status ?? '', 'rented' ); ?>>Bérbe adva</option>
                            <option value="inactive" <?php selected( $parcel->status ?? '', 'inactive' ); ?>>Inaktív</option>
                        </select>
                    </div>

                    <div class="agro-form-group agro-form-full">
                        <label for="notes">Megjegyzések</label>
                        <textarea id="notes" name="notes" rows="4" class="agro-input"><?php echo esc_textarea( $parcel->notes ?? '' ); ?></textarea>
                    </div>
                </div>

                <div class="agro-form-actions">
                    <button type="submit" class="agro-btn agro-btn-primary agro-btn-lg">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo $is_edit ? 'Módosítások mentése' : 'Parcella mentése'; ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Get human-readable status label.
     */
    public function get_status_label( $status ) {
        $labels = array(
            'active'   => 'Aktív',
            'fallow'   => 'Pihentetett',
            'rented'   => 'Bérbe adva',
            'inactive' => 'Inaktív',
        );
        return $labels[ $status ] ?? $status;
    }
}
