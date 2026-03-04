<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AgroManager_Finances {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'agro_finances';
    }

    public function get_all( $args = array() ) {
        global $wpdb;
        $args = wp_parse_args($args, array('type'=>'','category'=>'','parcel_id'=>0,'search'=>'','date_from'=>'','date_to'=>'','orderby'=>'date','order'=>'DESC'));
        $pt = $wpdb->prefix.'agro_parcels'; $ct = $wpdb->prefix.'agro_crops';
        $where = '1=1'; $params = array();
        if (!empty($args['type'])) { $where .= ' AND f.type = %s'; $params[] = $args['type']; }
        if (!empty($args['parcel_id'])) { $where .= ' AND f.parcel_id = %d'; $params[] = intval($args['parcel_id']); }
        if (!empty($args['date_from'])) { $where .= ' AND f.date >= %s'; $params[] = $args['date_from']; }
        if (!empty($args['date_to'])) { $where .= ' AND f.date <= %s'; $params[] = $args['date_to']; }
        if (!empty($args['search'])) { $where .= ' AND (f.category LIKE %s OR f.notes LIKE %s)'; $s='%'.$wpdb->esc_like($args['search']).'%'; $params[]=$s; $params[]=$s; }

        $sql = "SELECT f.*, p.name as parcel_name, cr.crop_name FROM {$this->table_name} f LEFT JOIN {$pt} p ON f.parcel_id=p.id LEFT JOIN {$ct} cr ON f.crop_id=cr.id WHERE {$where} ORDER BY f.date DESC, f.id DESC";
        return !empty($params) ? $wpdb->get_results($wpdb->prepare($sql,$params)) : $wpdb->get_results($sql);
    }

    public function get($id) { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id=%d",intval($id))); }

    public function insert($data) { global $wpdb; $wpdb->insert($this->table_name,$this->sanitize_data($data)); return $wpdb->insert_id; }

    public function update($id,$data) { global $wpdb; return $wpdb->update($this->table_name,$this->sanitize_data($data),array('id'=>intval($id))); }

    public function delete($id) { global $wpdb; return $wpdb->delete($this->table_name,array('id'=>intval($id))); }

    public function get_summary($year=null) {
        global $wpdb;
        if(!$year) $year = date('Y');
        $income = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$this->table_name} WHERE type='income' AND YEAR(date)=%d",$year));
        $expense = (float)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(amount),0) FROM {$this->table_name} WHERE type='expense' AND YEAR(date)=%d",$year));
        return array('income'=>$income,'expense'=>$expense,'profit'=>$income-$expense);
    }

    public function get_monthly_summary($year=null) {
        global $wpdb;
        if(!$year) $year = date('Y');
        return $wpdb->get_results($wpdb->prepare(
            "SELECT MONTH(date) as month, type, SUM(amount) as total FROM {$this->table_name} WHERE YEAR(date)=%d GROUP BY MONTH(date), type ORDER BY month",$year
        ));
    }

    private function sanitize_data($data) {
        return array(
            'type' => in_array($data['type']??'',array('income','expense')) ? $data['type'] : 'expense',
            'category' => sanitize_text_field($data['category']??''),
            'amount' => floatval($data['amount']??0),
            'date' => sanitize_text_field($data['date']??date('Y-m-d')),
            'parcel_id' => !empty($data['parcel_id']) ? intval($data['parcel_id']) : null,
            'crop_id' => !empty($data['crop_id']) ? intval($data['crop_id']) : null,
            'notes' => sanitize_textarea_field($data['notes']??''),
        );
    }

    public function render_list_page() {
        if (isset($_GET['action'])&&$_GET['action']==='delete'&&isset($_GET['id'])) {
            if (check_admin_referer('agro_delete_finance_'.$_GET['id'])) { $this->delete(intval($_GET['id'])); echo '<div class="notice notice-success"><p>Tétel törölve.</p></div>'; }
        }
        $search = isset($_GET['s'])?sanitize_text_field($_GET['s']):'';
        $type_filter = isset($_GET['type_filter'])?sanitize_text_field($_GET['type_filter']):'';
        $items = $this->get_all(array('search'=>$search,'type'=>$type_filter));
        $summary = $this->get_summary();
        $currency = get_option('agromanager_currency','HUF');
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1>💰 Pénzügyek</h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-finances&action=add')); ?>" class="agro-btn agro-btn-primary"><span class="dashicons dashicons-plus-alt2"></span> Új tétel</a>
            </div>

            <div class="agro-summary-cards agro-summary-cards-3">
                <div class="agro-summary-card agro-card-income"><div class="agro-summary-label">Bevételek (<?php echo date('Y'); ?>)</div><div class="agro-summary-value"><?php echo number_format($summary['income'],0,',',' '); ?> <?php echo esc_html($currency); ?></div></div>
                <div class="agro-summary-card agro-card-expense"><div class="agro-summary-label">Kiadások (<?php echo date('Y'); ?>)</div><div class="agro-summary-value"><?php echo number_format($summary['expense'],0,',',' '); ?> <?php echo esc_html($currency); ?></div></div>
                <div class="agro-summary-card <?php echo $summary['profit']>=0?'agro-card-profit':'agro-card-loss'; ?>"><div class="agro-summary-label">Eredmény</div><div class="agro-summary-value"><?php echo ($summary['profit']>=0?'+':'').number_format($summary['profit'],0,',',' '); ?> <?php echo esc_html($currency); ?></div></div>
            </div>

            <div class="agromanager-filter-bar">
                <form method="get"><input type="hidden" name="page" value="agromanager-finances">
                <select name="type_filter" class="agro-input"><option value="">Összes típus</option><option value="income" <?php selected($type_filter,'income');?>>Bevétel</option><option value="expense" <?php selected($type_filter,'expense');?>>Kiadás</option></select>
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Keresés..." class="agro-search-input">
                <button type="submit" class="agro-btn agro-btn-secondary">Szűrés</button></form>
            </div>

            <?php if (empty($items)): ?>
                <div class="agromanager-empty-state"><span class="dashicons dashicons-chart-bar" style="font-size:48px;color:#94a3b8;"></span><h3>Még nincsenek tételek</h3></div>
            <?php else: ?>
                <div class="agromanager-table-wrap"><table class="agromanager-table"><thead><tr><th>Dátum</th><th>Típus</th><th>Kategória</th><th>Összeg</th><th>Parcella</th><th>Kultúra</th><th>Megjegyzés</th><th>Műveletek</th></tr></thead><tbody>
                <?php foreach($items as $item): ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n('Y.m.d',strtotime($item->date))); ?></td>
                        <td><span class="agro-badge agro-badge-<?php echo esc_attr($item->type); ?>"><?php echo $item->type==='income'?'Bevétel':'Kiadás'; ?></span></td>
                        <td><?php echo esc_html($item->category); ?></td>
                        <td class="agro-amount-<?php echo esc_attr($item->type); ?>"><strong><?php echo ($item->type==='income'?'+':'-').number_format($item->amount,0,',',' '); ?> <?php echo esc_html($currency); ?></strong></td>
                        <td><?php echo esc_html($item->parcel_name??'—'); ?></td>
                        <td><?php echo esc_html($item->crop_name??'—'); ?></td>
                        <td><?php echo esc_html(wp_trim_words($item->notes,5)); ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-finances&action=edit&id='.$item->id)); ?>" class="agro-btn agro-btn-sm agro-btn-edit"><span class="dashicons dashicons-edit"></span></a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=agromanager-finances&action=delete&id='.$item->id),'agro_delete_finance_'.$item->id); ?>" class="agro-btn agro-btn-sm agro-btn-delete" onclick="return confirm('Biztosan?');"><span class="dashicons dashicons-trash"></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_form_page() {
        $id = isset($_GET['id'])?intval($_GET['id']):0;
        $item = $id ? $this->get($id) : null;
        $is_edit = !empty($item);

        if (isset($_POST['agro_finance_nonce'])&&wp_verify_nonce($_POST['agro_finance_nonce'],'agro_save_finance')) {
            if ($is_edit) { $this->update($id,$_POST); $item=$this->get($id); echo '<div class="notice notice-success"><p>Tétel frissítve.</p></div>';
            } else { $new_id=$this->insert($_POST); if($new_id){wp_redirect(admin_url('admin.php?page=agromanager-finances&action=edit&id='.$new_id.'&saved=1'));exit;} }
        }
        if (isset($_GET['saved'])) echo '<div class="notice notice-success"><p>Tétel mentve.</p></div>';

        $parcels = new AgroManager_Parcels(); $parcel_opts = $parcels->get_dropdown_options();
        $categories = array('vetőmag'=>'Vetőmag','műtrágya'=>'Műtrágya','növényvédőszer'=>'Növényvédő szer','üzemanyag'=>'Üzemanyag','szerviz'=>'Szerviz/karbantartás','munkabér'=>'Munkabér','bérleti_díj'=>'Bérleti díj','biztosítás'=>'Biztosítás','értékesítés'=>'Értékesítés','támogatás'=>'Támogatás','egyéb'=>'Egyéb');
        $currency = get_option('agromanager_currency','HUF');
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1><?php echo $is_edit ? '✏️ Tétel szerkesztése':'➕ Új pénzügyi tétel'; ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-finances')); ?>" class="agro-btn agro-btn-secondary">← Vissza</a>
            </div>
            <form method="post" class="agromanager-form"><?php wp_nonce_field('agro_save_finance','agro_finance_nonce'); ?>
            <div class="agro-form-grid">
                <div class="agro-form-group"><label for="type">Típus *</label><select id="type" name="type" class="agro-input" required><option value="expense" <?php selected($item->type??'expense','expense');?>>Kiadás</option><option value="income" <?php selected($item->type??'','income');?>>Bevétel</option></select></div>
                <div class="agro-form-group"><label for="category">Kategória</label><select id="category" name="category" class="agro-input"><option value="">– Válassz –</option><?php foreach($categories as $v=>$l):?><option value="<?php echo esc_attr($v);?>" <?php selected($item->category??'',$v);?>><?php echo esc_html($l);?></option><?php endforeach;?></select></div>
                <div class="agro-form-group"><label for="amount">Összeg (<?php echo esc_html($currency); ?>) *</label><input type="number" id="amount" name="amount" value="<?php echo esc_attr($item->amount??''); ?>" step="1" min="0" required class="agro-input"></div>
                <div class="agro-form-group"><label for="date">Dátum *</label><input type="date" id="date" name="date" value="<?php echo esc_attr($item->date??date('Y-m-d')); ?>" required class="agro-input"></div>
                <div class="agro-form-group"><label for="parcel_id">Parcella</label><select id="parcel_id" name="parcel_id" class="agro-input"><option value="">– Nincs –</option><?php foreach($parcel_opts as $pid=>$pl):?><option value="<?php echo esc_attr($pid);?>" <?php selected($item->parcel_id??'',$pid);?>><?php echo esc_html($pl);?></option><?php endforeach;?></select></div>
                <div class="agro-form-group"><label for="crop_id">Kultúra ID</label><input type="number" id="crop_id" name="crop_id" value="<?php echo esc_attr($item->crop_id??''); ?>" min="0" class="agro-input"></div>
                <div class="agro-form-group agro-form-full"><label for="notes">Megjegyzések</label><textarea id="notes" name="notes" rows="3" class="agro-input"><?php echo esc_textarea($item->notes??''); ?></textarea></div>
            </div>
            <div class="agro-form-actions"><button type="submit" class="agro-btn agro-btn-primary agro-btn-lg"><span class="dashicons dashicons-saved"></span> <?php echo $is_edit?'Mentés':'Tétel mentése'; ?></button></div>
            </form>
        </div>
        <?php
    }
}
