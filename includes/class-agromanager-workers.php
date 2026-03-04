<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class AgroManager_Workers {
    private $table_name;
    private $logs_table;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'agro_workers';
        $this->logs_table = $wpdb->prefix . 'agro_work_logs';
    }

    public function get_all($args=array()) {
        global $wpdb;
        $args = wp_parse_args($args, array('status'=>'','search'=>'','orderby'=>'name','order'=>'ASC'));
        $where='1=1'; $params=array();
        if(!empty($args['status'])){$where.=' AND status=%s';$params[]=$args['status'];}
        if(!empty($args['search'])){$where.=' AND (name LIKE %s OR position LIKE %s)';$s='%'.$wpdb->esc_like($args['search']).'%';$params[]=$s;$params[]=$s;}
        $sql = "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY name ASC";
        return !empty($params)?$wpdb->get_results($wpdb->prepare($sql,$params)):$wpdb->get_results($sql);
    }

    public function get($id) { global $wpdb; return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id=%d",intval($id))); }
    public function insert($data) { global $wpdb; $wpdb->insert($this->table_name,$this->sanitize_data($data)); return $wpdb->insert_id; }
    public function update($id,$data) { global $wpdb; return $wpdb->update($this->table_name,$this->sanitize_data($data),array('id'=>intval($id))); }
    public function delete($id) { global $wpdb; return $wpdb->delete($this->table_name,array('id'=>intval($id))); }

    public function count($status='') {
        global $wpdb;
        if(!empty($status)) return (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE status=%s",$status));
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
    }

    private function sanitize_data($data) {
        return array(
            'name'=>sanitize_text_field($data['name']??''), 'position'=>sanitize_text_field($data['position']??''),
            'phone'=>sanitize_text_field($data['phone']??''), 'email'=>sanitize_email($data['email']??''),
            'status'=>sanitize_text_field($data['status']??'active'),
        );
    }

    // Work logs
    public function get_logs($args=array()) {
        global $wpdb;
        $args = wp_parse_args($args, array('worker_id'=>0,'date_from'=>'','date_to'=>'','parcel_id'=>0));
        $where='1=1'; $params=array();
        if(!empty($args['worker_id'])){$where.=' AND l.worker_id=%d';$params[]=intval($args['worker_id']);}
        if(!empty($args['date_from'])){$where.=' AND l.date>=%s';$params[]=$args['date_from'];}
        if(!empty($args['date_to'])){$where.=' AND l.date<=%s';$params[]=$args['date_to'];}
        $pt=$wpdb->prefix.'agro_parcels';
        $sql="SELECT l.*, w.name as worker_name, p.name as parcel_name FROM {$this->logs_table} l LEFT JOIN {$this->table_name} w ON l.worker_id=w.id LEFT JOIN {$pt} p ON l.parcel_id=p.id WHERE {$where} ORDER BY l.date DESC, l.start_time DESC";
        return !empty($params)?$wpdb->get_results($wpdb->prepare($sql,$params)):$wpdb->get_results($sql);
    }

    public function insert_log($data) {
        global $wpdb;
        $wpdb->insert($this->logs_table, array(
            'worker_id'=>intval($data['worker_id']??0), 'date'=>sanitize_text_field($data['log_date']??date('Y-m-d')),
            'start_time'=>sanitize_text_field($data['start_time']??''), 'end_time'=>sanitize_text_field($data['end_time']??''),
            'activity'=>sanitize_text_field($data['activity']??''),
            'parcel_id'=>!empty($data['parcel_id'])?intval($data['parcel_id']):null,
            'notes'=>sanitize_textarea_field($data['log_notes']??''),
        ));
        return $wpdb->insert_id;
    }

    public function delete_log($id) { global $wpdb; return $wpdb->delete($this->logs_table,array('id'=>intval($id))); }

    public function render_list_page() {
        if (isset($_GET['action'])&&$_GET['action']==='delete'&&isset($_GET['id'])) {
            if(check_admin_referer('agro_delete_worker_'.$_GET['id'])){$this->delete(intval($_GET['id']));echo '<div class="notice notice-success"><p>Dolgozó törölve.</p></div>';}
        }
        $search=isset($_GET['s'])?sanitize_text_field($_GET['s']):'';
        $workers=$this->get_all(array('search'=>$search));
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1>👷 Dolgozók</h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-workers&action=add')); ?>" class="agro-btn agro-btn-primary"><span class="dashicons dashicons-plus-alt2"></span> Új dolgozó</a>
            </div>
            <div class="agromanager-filter-bar">
                <form method="get"><input type="hidden" name="page" value="agromanager-workers">
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Keresés..." class="agro-search-input">
                <button type="submit" class="agro-btn agro-btn-secondary">Keresés</button></form>
            </div>
            <?php if(empty($workers)): ?>
                <div class="agromanager-empty-state"><span class="dashicons dashicons-groups" style="font-size:48px;color:#94a3b8;"></span><h3>Még nincsenek dolgozók</h3></div>
            <?php else: ?>
                <div class="agromanager-table-wrap"><table class="agromanager-table"><thead><tr><th>Név</th><th>Beosztás</th><th>Telefon</th><th>Email</th><th>Státusz</th><th>Műveletek</th></tr></thead><tbody>
                <?php foreach($workers as $w): ?>
                    <tr>
                        <td><strong><?php echo esc_html($w->name); ?></strong></td>
                        <td><?php echo esc_html($w->position); ?></td>
                        <td><?php echo esc_html($w->phone); ?></td>
                        <td><a href="mailto:<?php echo esc_attr($w->email); ?>"><?php echo esc_html($w->email); ?></a></td>
                        <td><span class="agro-badge agro-badge-<?php echo esc_attr($w->status); ?>"><?php echo $w->status==='active'?'Aktív':'Inaktív'; ?></span></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-workers&action=edit&id='.$w->id)); ?>" class="agro-btn agro-btn-sm agro-btn-edit"><span class="dashicons dashicons-edit"></span></a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-workers&action=worklog&id='.$w->id)); ?>" class="agro-btn agro-btn-sm agro-btn-secondary" title="Munkaidő"><span class="dashicons dashicons-clock"></span></a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=agromanager-workers&action=delete&id='.$w->id),'agro_delete_worker_'.$w->id); ?>" class="agro-btn agro-btn-sm agro-btn-delete" onclick="return confirm('Biztosan?');"><span class="dashicons dashicons-trash"></span></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_form_page() {
        $id=isset($_GET['id'])?intval($_GET['id']):0; $worker=$id?$this->get($id):null; $is_edit=!empty($worker);
        if(isset($_POST['agro_worker_nonce'])&&wp_verify_nonce($_POST['agro_worker_nonce'],'agro_save_worker')) {
            if($is_edit){$this->update($id,$_POST);$worker=$this->get($id);echo '<div class="notice notice-success"><p>Dolgozó frissítve.</p></div>';
            }else{$new_id=$this->insert($_POST);if($new_id){wp_redirect(admin_url('admin.php?page=agromanager-workers&action=edit&id='.$new_id.'&saved=1'));exit;}}
        }
        if(isset($_GET['saved']))echo '<div class="notice notice-success"><p>Dolgozó mentve.</p></div>';
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1><?php echo $is_edit?'✏️ Dolgozó szerkesztése':'➕ Új dolgozó'; ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-workers')); ?>" class="agro-btn agro-btn-secondary">← Vissza</a>
            </div>
            <form method="post" class="agromanager-form"><?php wp_nonce_field('agro_save_worker','agro_worker_nonce'); ?>
            <div class="agro-form-grid">
                <div class="agro-form-group"><label for="name">Név *</label><input type="text" id="name" name="name" value="<?php echo esc_attr($worker->name??''); ?>" required class="agro-input"></div>
                <div class="agro-form-group"><label for="position">Beosztás</label><input type="text" id="position" name="position" value="<?php echo esc_attr($worker->position??''); ?>" class="agro-input"></div>
                <div class="agro-form-group"><label for="phone">Telefon</label><input type="tel" id="phone" name="phone" value="<?php echo esc_attr($worker->phone??''); ?>" class="agro-input"></div>
                <div class="agro-form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="<?php echo esc_attr($worker->email??''); ?>" class="agro-input"></div>
                <div class="agro-form-group"><label for="status">Státusz</label><select id="status" name="status" class="agro-input"><option value="active" <?php selected($worker->status??'active','active');?>>Aktív</option><option value="inactive" <?php selected($worker->status??'','inactive');?>>Inaktív</option></select></div>
            </div>
            <div class="agro-form-actions"><button type="submit" class="agro-btn agro-btn-primary agro-btn-lg"><span class="dashicons dashicons-saved"></span> <?php echo $is_edit?'Mentés':'Dolgozó mentése'; ?></button></div>
            </form>
        </div>
        <?php
    }

    public function render_worklog_page() {
        $worker_id=isset($_GET['id'])?intval($_GET['id']):0;
        $worker=$this->get($worker_id);
        if(!$worker){echo '<div class="wrap"><p>Dolgozó nem található.</p></div>';return;}

        if(isset($_POST['agro_log_nonce'])&&wp_verify_nonce($_POST['agro_log_nonce'],'agro_save_log')) {
            $_POST['worker_id']=$worker_id;
            $this->insert_log($_POST);
            echo '<div class="notice notice-success"><p>Munkaidő rögzítve.</p></div>';
        }
        if(isset($_GET['delete_log'])&&check_admin_referer('agro_delete_log_'.$_GET['delete_log'])) {
            $this->delete_log(intval($_GET['delete_log']));
            echo '<div class="notice notice-success"><p>Bejegyzés törölve.</p></div>';
        }

        $logs=$this->get_logs(array('worker_id'=>$worker_id));
        $parcels=new AgroManager_Parcels(); $parcel_opts=$parcels->get_dropdown_options();
        ?>
        <div class="wrap agromanager-wrap">
            <div class="agromanager-page-header">
                <h1>⏰ Munkaidő – <?php echo esc_html($worker->name); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=agromanager-workers')); ?>" class="agro-btn agro-btn-secondary">← Vissza</a>
            </div>

            <div class="agro-worklog-form-wrap">
                <h3>Új bejegyzés</h3>
                <form method="post" class="agromanager-form"><?php wp_nonce_field('agro_save_log','agro_log_nonce'); ?>
                <div class="agro-form-grid">
                    <div class="agro-form-group"><label for="log_date">Dátum</label><input type="date" id="log_date" name="log_date" value="<?php echo date('Y-m-d'); ?>" class="agro-input"></div>
                    <div class="agro-form-group"><label for="start_time">Kezdés</label><input type="time" id="start_time" name="start_time" class="agro-input"></div>
                    <div class="agro-form-group"><label for="end_time">Befejezés</label><input type="time" id="end_time" name="end_time" class="agro-input"></div>
                    <div class="agro-form-group"><label for="activity">Tevékenység</label><input type="text" id="activity" name="activity" class="agro-input"></div>
                    <div class="agro-form-group"><label for="parcel_id">Parcella</label><select id="parcel_id" name="parcel_id" class="agro-input"><option value="">–</option><?php foreach($parcel_opts as $pid=>$pl):?><option value="<?php echo esc_attr($pid);?>"><?php echo esc_html($pl);?></option><?php endforeach;?></select></div>
                    <div class="agro-form-group"><label for="log_notes">Megjegyzés</label><input type="text" id="log_notes" name="log_notes" class="agro-input"></div>
                </div>
                <button type="submit" class="agro-btn agro-btn-primary">Rögzítés</button>
                </form>
            </div>

            <?php if(!empty($logs)): ?>
            <div class="agromanager-table-wrap" style="margin-top:20px;"><table class="agromanager-table"><thead><tr><th>Dátum</th><th>Kezdés</th><th>Befejezés</th><th>Óra</th><th>Tevékenység</th><th>Parcella</th><th>Törlés</th></tr></thead><tbody>
            <?php foreach($logs as $log):
                $hours='—';
                if($log->start_time&&$log->end_time){$diff=(strtotime($log->end_time)-strtotime($log->start_time))/3600;$hours=number_format($diff,1,',','').' h';}
            ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('Y.m.d',strtotime($log->date))); ?></td>
                    <td><?php echo esc_html($log->start_time??'—'); ?></td>
                    <td><?php echo esc_html($log->end_time??'—'); ?></td>
                    <td><strong><?php echo $hours; ?></strong></td>
                    <td><?php echo esc_html($log->activity); ?></td>
                    <td><?php echo esc_html($log->parcel_name??'—'); ?></td>
                    <td><a href="<?php echo wp_nonce_url(admin_url('admin.php?page=agromanager-workers&action=worklog&id='.$worker_id.'&delete_log='.$log->id),'agro_delete_log_'.$log->id); ?>" class="agro-btn agro-btn-sm agro-btn-delete" onclick="return confirm('Biztosan?');"><span class="dashicons dashicons-trash"></span></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table></div>
            <?php endif; ?>
        </div>
        <?php
    }
}
