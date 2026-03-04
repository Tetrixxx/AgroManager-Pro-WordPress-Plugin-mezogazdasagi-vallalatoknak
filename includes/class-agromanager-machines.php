<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AgroManager_Machines {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'agro_machines';
    }

    public function get_all( $args = array() ) {
        global $wpdb;
        $args = wp_parse_args( $args, array( 'type' => '', 'search' => '', 'orderby' => 'name', 'order' => 'ASC' ) );
        $where = '1=1'; $params = array();
        if ( ! empty( $args['type'] ) ) { $where .= ' AND type = %s'; $params[] = $args['type']; }
        if ( ! empty( $args['search'] ) ) {
            $where .= ' AND (name LIKE %s OR manufacturer LIKE %s OR license_plate LIKE %s)';
            $s = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        $allowed = array('name','type','operating_hours','condition_status','next_service');
        $ob = in_array($args['orderby'], $allowed, true) ? $args['orderby'] : 'name';
        $o = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $sql = "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY {$ob} {$o}";
        return ! empty($params) ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
    }

    public function get( $id ) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", intval($id)));
    }

    public function insert( $data ) {
        global $wpdb; $wpdb->insert($this->table_name, $this->sanitize_data($data)); return $wpdb->insert_id;
    }

    public function update( $id, $data ) {
        global $wpdb; return $wpdb->update($this->table_name, $this->sanitize_data($data), array('id' => intval($id)));
    }

    public function delete( $id ) {
        global $wpdb; return $wpdb->delete($this->table_name, array('id' => intval($id)));
    }

    public function count( $condition = '' ) {
        global $wpdb;
        if ( ! empty($condition) ) { return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE condition_status = %s", $condition)); }
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }

    public function get_needing_service() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} WHERE next_service IS NOT NULL AND next_service <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY next_service ASC");
    }

    private function sanitize_data( $data ) {
        return array(
            'name' => sanitize_text_field($data['name'] ?? ''), 'type' => sanitize_text_field($data['type'] ?? ''),
            'manufacturer' => sanitize_text_field($data['manufacturer'] ?? ''), 'license_plate' => sanitize_text_field($data['license_plate'] ?? ''),
            'operating_hours' => intval($data['operating_hours'] ?? 0), 'condition_status' => sanitize_text_field($data['condition_status'] ?? 'operational'),
            'last_service' => !empty($data['last_service']) ? sanitize_text_field($data['last_service']) : null,
            'next_service' => !empty($data['next_service']) ? sanitize_text_field($data['next_service']) : null,
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
        );
    }

    public function render_list_page() {
        if ( isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) ) {
            if ( check_admin_referer('agro_delete_machine_' . $_GET['id']) ) { $this->delete(intval($_GET['id'])); echo '<div class="notice notice-success"><p>Gép sikeresen törölve.</p></div>'; }
        }
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $machines = $this->get_all(array('search' => $search));
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1>🚜 Géppark</h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-machines&action=add')); ?>" class="agro-btn agro-btn-primary"><span class="dashicons dashicons-plus-alt2"></span> Új gép</a>
            </div>
            <div class="agromanager-filter-bar">
                <form method="get"><input type="hidden" name="page" value="agromanager-machines">
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Keresés..." class="agro-search-input">
                <button type="submit" class="agro-btn agro-btn-secondary">Keresés</button></form>
            </div>
            <?php if ( empty($machines) ) : ?>
                <div class="agromanager-empty-state"><span class="dashicons dashicons-car" style="font-size:48px;color:#94a3b8;"></span><h3>Még nincsenek gépek</h3><p>Adj hozzá az első gépet!</p></div>
            <?php else : ?>
                <div class="agromanager-table-wrap"><table class="agromanager-table"><thead><tr>
                    <th>Megnevezés</th><th>Típus</th><th>Gyártó</th><th>Rendszám</th><th>Üzemóra</th><th>Állapot</th><th>Köv. szerviz</th><th>Műveletek</th>
                </tr></thead><tbody>
                <?php foreach ( $machines as $m ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($m->name); ?></strong></td>
                        <td><?php echo esc_html($this->get_type_label($m->type)); ?></td>
                        <td><?php echo esc_html($m->manufacturer); ?></td>
                        <td><code><?php echo esc_html($m->license_plate); ?></code></td>
                        <td><?php echo number_format($m->operating_hours,0,',',' '); ?> h</td>
                        <td><span class="agro-badge agro-badge-<?php echo esc_attr($m->condition_status); ?>"><?php echo esc_html($this->get_condition_label($m->condition_status)); ?></span></td>
                        <td><?php if($m->next_service){$d=(int)((strtotime($m->next_service)-time())/86400);$c=$d<=7?'agro-text-danger':($d<=30?'agro-text-warning':'');echo '<span class="'.esc_attr($c).'">'.esc_html(date_i18n('Y.m.d',strtotime($m->next_service))).'</span>';}else{echo '—';} ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-machines&action=edit&id='.$m->id)); ?>" class="agro-btn agro-btn-sm agro-btn-edit"><span class="dashicons dashicons-edit"></span></a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=agromanager-machines&action=delete&id='.$m->id),'agro_delete_machine_'.$m->id); ?>" class="agro-btn agro-btn-sm agro-btn-delete" onclick="return confirm('Biztosan törölni szeretnéd?');"><span class="dashicons dashicons-trash"></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_form_page() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $machine = $id ? $this->get($id) : null;
        $is_edit = !empty($machine);
        if ( isset($_POST['agro_machine_nonce']) && wp_verify_nonce($_POST['agro_machine_nonce'], 'agro_save_machine') ) {
            if ($is_edit) { $this->update($id, $_POST); $machine = $this->get($id); echo '<div class="notice notice-success"><p>Gép frissítve.</p></div>';
            } else { $new_id = $this->insert($_POST); if($new_id){ wp_redirect(admin_url('admin.php?page=agromanager-machines&action=edit&id='.$new_id.'&saved=1')); exit; } }
        }
        if (isset($_GET['saved'])) echo '<div class="notice notice-success"><p>Gép sikeresen mentve.</p></div>';
        $types = array('tractor'=>'Traktor','combine'=>'Kombájn','sprayer'=>'Permetező','seeder'=>'Vetőgép','plow'=>'Eke','trailer'=>'Pótkocsi','truck'=>'Teherautó','other'=>'Egyéb');
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1><?php echo $is_edit ? '✏️ Gép szerkesztése' : '➕ Új gép'; ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-machines')); ?>" class="agro-btn agro-btn-secondary">← Vissza</a>
            </div>
            <form method="post" class="agromanager-form"><?php wp_nonce_field('agro_save_machine', 'agro_machine_nonce'); ?>
            <div class="agro-form-grid">
                <div class="agro-form-group"><label for="name">Megnevezés *</label><input type="text" id="name" name="name" value="<?php echo esc_attr($machine->name ?? ''); ?>" required class="agro-input"></div>
                <div class="agro-form-group"><label for="type">Típus</label><select id="type" name="type" class="agro-input"><option value="">– Válassz –</option><?php foreach($types as $v=>$l): ?><option value="<?php echo esc_attr($v);?>" <?php selected($machine->type??'',$v);?>><?php echo esc_html($l);?></option><?php endforeach;?></select></div>
                <div class="agro-form-group"><label for="manufacturer">Gyártó</label><input type="text" id="manufacturer" name="manufacturer" value="<?php echo esc_attr($machine->manufacturer ?? ''); ?>" class="agro-input"></div>
                <div class="agro-form-group"><label for="license_plate">Rendszám</label><input type="text" id="license_plate" name="license_plate" value="<?php echo esc_attr($machine->license_plate ?? ''); ?>" class="agro-input"></div>
                <div class="agro-form-group"><label for="operating_hours">Üzemóra</label><input type="number" id="operating_hours" name="operating_hours" value="<?php echo esc_attr($machine->operating_hours ?? 0); ?>" min="0" class="agro-input"></div>
                <div class="agro-form-group"><label for="condition_status">Állapot</label><select id="condition_status" name="condition_status" class="agro-input">
                    <option value="operational" <?php selected($machine->condition_status??'operational','operational');?>>Üzemképes</option>
                    <option value="maintenance" <?php selected($machine->condition_status??'','maintenance');?>>Karbantartás</option>
                    <option value="broken" <?php selected($machine->condition_status??'','broken');?>>Meghibásodott</option>
                    <option value="retired" <?php selected($machine->condition_status??'','retired');?>>Kivonva</option>
                </select></div>
                <div class="agro-form-group"><label for="last_service">Utolsó szerviz</label><input type="date" id="last_service" name="last_service" value="<?php echo esc_attr($machine->last_service ?? ''); ?>" class="agro-input"></div>
                <div class="agro-form-group"><label for="next_service">Következő szerviz</label><input type="date" id="next_service" name="next_service" value="<?php echo esc_attr($machine->next_service ?? ''); ?>" class="agro-input"></div>
                <div class="agro-form-group agro-form-full"><label for="notes">Megjegyzések</label><textarea id="notes" name="notes" rows="4" class="agro-input"><?php echo esc_textarea($machine->notes ?? ''); ?></textarea></div>
            </div>
            <div class="agro-form-actions"><button type="submit" class="agro-btn agro-btn-primary agro-btn-lg"><span class="dashicons dashicons-saved"></span> <?php echo $is_edit ? 'Mentés' : 'Gép mentése'; ?></button></div>
            </form>
        </div>
        <?php
    }

    public function get_type_label($t) {
        $l = array('tractor'=>'Traktor','combine'=>'Kombájn','sprayer'=>'Permetező','seeder'=>'Vetőgép','plow'=>'Eke','trailer'=>'Pótkocsi','truck'=>'Teherautó','other'=>'Egyéb');
        return $l[$t] ?? $t;
    }
    public function get_condition_label($s) {
        $l = array('operational'=>'Üzemképes','maintenance'=>'Karbantartás','broken'=>'Meghibásodott','retired'=>'Kivonva');
        return $l[$s] ?? $s;
    }
}
