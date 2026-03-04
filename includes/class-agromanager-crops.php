<?php
/**
 * AgroManager Pro – Crops Module
 *
 * Manages crop records linked to parcels.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AgroManager_Crops {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'agro_crops';
    }

    public function get_all( $args = array() ) {
        global $wpdb;
        $defaults = array(
            'parcel_id' => 0,
            'status'    => '',
            'search'    => '',
            'orderby'   => 'sowing_date',
            'order'     => 'DESC',
        );
        $args = wp_parse_args( $args, $defaults );

        $parcels_table = $wpdb->prefix . 'agro_parcels';
        $where  = '1=1';
        $params = array();

        if ( ! empty( $args['parcel_id'] ) ) {
            $where .= ' AND c.parcel_id = %d';
            $params[] = intval( $args['parcel_id'] );
        }
        if ( ! empty( $args['status'] ) ) {
            $where .= ' AND c.status = %s';
            $params[] = $args['status'];
        }
        if ( ! empty( $args['search'] ) ) {
            $where .= ' AND c.crop_name LIKE %s';
            $params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        }

        $allowed_orderby = array( 'crop_name', 'sowing_date', 'expected_harvest', 'sown_area_ha', 'status' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'sowing_date';
        $order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT c.*, p.name as parcel_name 
                FROM {$this->table_name} c 
                LEFT JOIN {$parcels_table} p ON c.parcel_id = p.id 
                WHERE {$where} 
                ORDER BY c.{$orderby} {$order}";

        if ( ! empty( $params ) ) {
            $sql = $wpdb->prepare( $sql, $params );
        }

        return $wpdb->get_results( $sql );
    }

    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            intval( $id )
        ) );
    }

    public function insert( $data ) {
        global $wpdb;
        $sanitized = $this->sanitize_data( $data );
        $wpdb->insert( $this->table_name, $sanitized );
        return $wpdb->insert_id;
    }

    public function update( $id, $data ) {
        global $wpdb;
        $sanitized = $this->sanitize_data( $data );
        return $wpdb->update( $this->table_name, $sanitized, array( 'id' => intval( $id ) ) );
    }

    public function delete( $id ) {
        global $wpdb;
        return $wpdb->delete( $this->table_name, array( 'id' => intval( $id ) ) );
    }

    public function count( $status = '' ) {
        global $wpdb;
        if ( ! empty( $status ) ) {
            return (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE status = %s", $status
            ) );
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}" );
    }

    public function total_sown_area() {
        global $wpdb;
        return (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(sown_area_ha), 0) FROM {$this->table_name} WHERE status IN ('active', 'planted', 'growing')"
        );
    }

    private function sanitize_data( $data ) {
        return array(
            'parcel_id'        => ! empty( $data['parcel_id'] ) ? intval( $data['parcel_id'] ) : null,
            'crop_name'        => sanitize_text_field( $data['crop_name'] ?? '' ),
            'sowing_date'      => sanitize_text_field( $data['sowing_date'] ?? '' ),
            'expected_harvest'  => sanitize_text_field( $data['expected_harvest'] ?? '' ),
            'sown_area_ha'     => floatval( $data['sown_area_ha'] ?? 0 ),
            'expected_yield'   => floatval( $data['expected_yield'] ?? 0 ),
            'actual_yield'     => floatval( $data['actual_yield'] ?? 0 ),
            'status'           => sanitize_text_field( $data['status'] ?? 'planned' ),
            'notes'            => sanitize_textarea_field( $data['notes'] ?? '' ),
        );
    }

    public function render_list_page() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            if ( check_admin_referer( 'agro_delete_crop_' . $_GET['id'] ) ) {
                $this->delete( intval( $_GET['id'] ) );
                echo '<div class="notice notice-success"><p>Kultúra sikeresen törölve.</p></div>';
            }
        }

        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $crops = $this->get_all( array( 'search' => $search ) );

        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1>🌱 Növénykultúrák</h1>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=agromanager-crops&action=add' ) ); ?>" class="agro-btn agro-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span> Új kultúra
                </a>
            </div>

            <div class="agromanager-filter-bar">
                <form method="get" action="">
                    <input type="hidden" name="page" value="agromanager-crops">
                    <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Keresés kultúra neve alapján..." class="agro-search-input">
                    <button type="submit" class="agro-btn agro-btn-secondary">Keresés</button>
                </form>
            </div>

            <?php if ( empty( $crops ) ) : ?>
                <div class="agromanager-empty-state">
                    <span class="dashicons dashicons-carrot" style="font-size:48px;color:#94a3b8;"></span>
                    <h3>Még nincsenek kultúrák</h3>
                    <p>Adj hozzá az első növénykultúrát!</p>
                </div>
            <?php else : ?>
                <div class="agromanager-table-wrap">
                    <table class="agromanager-table">
                        <thead>
                            <tr>
                                <th>Kultúra</th>
                                <th>Parcella</th>
                                <th>Vetés</th>
                                <th>Betakarítás</th>
                                <th>Terület (ha)</th>
                                <th>Várh. hozam</th>
                                <th>Tény hozam</th>
                                <th>Státusz</th>
                                <th>Műveletek</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $crops as $crop ) : ?>
                                <tr>
                                    <td><strong><?php echo esc_html( $crop->crop_name ); ?></strong></td>
                                    <td><?php echo esc_html( $crop->parcel_name ?? '—' ); ?></td>
                                    <td><?php echo $crop->sowing_date ? esc_html( date_i18n( 'Y.m.d', strtotime( $crop->sowing_date ) ) ) : '—'; ?></td>
                                    <td><?php echo $crop->expected_harvest ? esc_html( date_i18n( 'Y.m.d', strtotime( $crop->expected_harvest ) ) ) : '—'; ?></td>
                                    <td><?php echo esc_html( number_format( $crop->sown_area_ha, 2, ',', ' ' ) ); ?></td>
                                    <td><?php echo esc_html( number_format( $crop->expected_yield, 2, ',', ' ' ) ); ?> t</td>
                                    <td><?php echo esc_html( number_format( $crop->actual_yield, 2, ',', ' ' ) ); ?> t</td>
                                    <td>
                                        <span class="agro-badge agro-badge-<?php echo esc_attr( $crop->status ); ?>">
                                            <?php echo esc_html( $this->get_status_label( $crop->status ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=agromanager-crops&action=edit&id=' . $crop->id ) ); ?>" class="agro-btn agro-btn-sm agro-btn-edit"><span class="dashicons dashicons-edit"></span></a>
                                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=agromanager-crops&action=delete&id=' . $crop->id ), 'agro_delete_crop_' . $crop->id ); ?>" class="agro-btn agro-btn-sm agro-btn-delete" onclick="return confirm('Biztosan törölni szeretnéd?');"><span class="dashicons dashicons-trash"></span></a>
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

    public function render_form_page() {
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $crop = $id ? $this->get( $id ) : null;
        $is_edit = ! empty( $crop );

        if ( isset( $_POST['agro_crop_nonce'] ) && wp_verify_nonce( $_POST['agro_crop_nonce'], 'agro_save_crop' ) ) {
            if ( $is_edit ) {
                $this->update( $id, $_POST );
                $crop = $this->get( $id );
                echo '<div class="notice notice-success"><p>Kultúra sikeresen frissítve.</p></div>';
            } else {
                $new_id = $this->insert( $_POST );
                if ( $new_id ) {
                    wp_redirect( admin_url( 'admin.php?page=agromanager-crops&action=edit&id=' . $new_id . '&saved=1' ) );
                    exit;
                }
            }
        }

        if ( isset( $_GET['saved'] ) ) {
            echo '<div class="notice notice-success"><p>Kultúra sikeresen mentve.</p></div>';
        }

        $parcels_module = new AgroManager_Parcels();
        $parcel_options = $parcels_module->get_dropdown_options();

        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1><?php echo $is_edit ? '✏️ Kultúra szerkesztése' : '➕ Új kultúra'; ?></h1>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=agromanager-crops' ) ); ?>" class="agro-btn agro-btn-secondary">← Vissza a listához</a>
            </div>

            <form method="post" class="agromanager-form">
                <?php wp_nonce_field( 'agro_save_crop', 'agro_crop_nonce' ); ?>

                <div class="agro-form-grid">
                    <div class="agro-form-group">
                        <label for="crop_name">Kultúra neve *</label>
                        <input type="text" id="crop_name" name="crop_name" value="<?php echo esc_attr( $crop->crop_name ?? '' ); ?>" required class="agro-input">
                    </div>

                    <div class="agro-form-group">
                        <label for="parcel_id">Parcella</label>
                        <select id="parcel_id" name="parcel_id" class="agro-input">
                            <option value="">– Válassz parcellát –</option>
                            <?php foreach ( $parcel_options as $pid => $plabel ) : ?>
                                <option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $crop->parcel_id ?? '', $pid ); ?>>
                                    <?php echo esc_html( $plabel ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="agro-form-group">
                        <label for="sowing_date">Vetés dátuma</label>
                        <input type="date" id="sowing_date" name="sowing_date" value="<?php echo esc_attr( $crop->sowing_date ?? '' ); ?>" class="agro-input">
                    </div>

                    <div class="agro-form-group">
                        <label for="expected_harvest">Várható betakarítás</label>
                        <input type="date" id="expected_harvest" name="expected_harvest" value="<?php echo esc_attr( $crop->expected_harvest ?? '' ); ?>" class="agro-input">
                    </div>

                    <div class="agro-form-group">
                        <label for="sown_area_ha">Vetett terület (ha)</label>
                        <input type="number" id="sown_area_ha" name="sown_area_ha" value="<?php echo esc_attr( $crop->sown_area_ha ?? '' ); ?>" step="0.01" min="0" class="agro-input">
                    </div>

                    <div class="agro-form-group">
                        <label for="expected_yield">Várható hozam (tonna)</label>
                        <input type="number" id="expected_yield" name="expected_yield" value="<?php echo esc_attr( $crop->expected_yield ?? '' ); ?>" step="0.01" min="0" class="agro-input">
                    </div>

                    <div class="agro-form-group">
                        <label for="actual_yield">Tényleges hozam (tonna)</label>
                        <input type="number" id="actual_yield" name="actual_yield" value="<?php echo esc_attr( $crop->actual_yield ?? '' ); ?>" step="0.01" min="0" class="agro-input">
                    </div>

                    <div class="agro-form-group">
                        <label for="status">Státusz</label>
                        <select id="status" name="status" class="agro-input">
                            <option value="planned" <?php selected( $crop->status ?? 'planned', 'planned' ); ?>>Tervezett</option>
                            <option value="planted" <?php selected( $crop->status ?? '', 'planted' ); ?>>Elvetve</option>
                            <option value="growing" <?php selected( $crop->status ?? '', 'growing' ); ?>>Növekedésben</option>
                            <option value="harvested" <?php selected( $crop->status ?? '', 'harvested' ); ?>>Betakarítva</option>
                            <option value="failed" <?php selected( $crop->status ?? '', 'failed' ); ?>>Meghiúsult</option>
                        </select>
                    </div>

                    <div class="agro-form-group agro-form-full">
                        <label for="notes">Megjegyzések</label>
                        <textarea id="notes" name="notes" rows="4" class="agro-input"><?php echo esc_textarea( $crop->notes ?? '' ); ?></textarea>
                    </div>
                </div>

                <div class="agro-form-actions">
                    <button type="submit" class="agro-btn agro-btn-primary agro-btn-lg">
                        <span class="dashicons dashicons-saved"></span>
                        <?php echo $is_edit ? 'Módosítások mentése' : 'Kultúra mentése'; ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    public function get_status_label( $status ) {
        $labels = array(
            'planned'   => 'Tervezett',
            'planted'   => 'Elvetve',
            'growing'   => 'Növekedésben',
            'harvested' => 'Betakarítva',
            'failed'    => 'Meghiúsult',
        );
        return $labels[ $status ] ?? $status;
    }
}
