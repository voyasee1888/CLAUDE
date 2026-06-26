<?php
defined('ABSPATH') || exit;

final class VSB_Source_Monitor {
    public static function init(): void {
        add_action('vsb_source_monitor_tick', [self::class,'run_batch']);
        add_filter('cron_schedules', [self::class,'schedule_interval']);
    }
    public static function schedule_interval(array $schedules): array {
        $schedules['vsb_twicedaily_spread']=['interval'=>12*HOUR_IN_SECONDS,'display'=>__('Twice daily, spread checks','voyasee-bagfit')]; return $schedules;
    }
    public static function schedule(): void {
        if(!wp_next_scheduled('vsb_source_monitor_tick')) wp_schedule_event(time()+HOUR_IN_SECONDS,'vsb_twicedaily_spread','vsb_source_monitor_tick');
    }
    public static function run_batch(): void {
        $s=get_option('vsb_settings',[]); if(empty($s['source_monitor_enabled']))return;
        global $wpdb; $batch=max(1,min(5,(int)($s['source_monitor_batch']??2)));
        $rows=$wpdb->get_results('SELECT a.id,a.source_url FROM '.VSB_DB::airlines_table().' a LEFT JOIN '.VSB_DB::source_table().' s ON s.airline_id=a.id WHERE a.status="published" AND a.source_url<>"" ORDER BY COALESCE(s.checked_at,"1970-01-01") ASC LIMIT '.(int)$batch,ARRAY_A);
        foreach($rows?:[] as $row) self::check((int)$row['id'],(string)$row['source_url']);
    }
    public static function check(int $airline_id,string $url): array {
        global $wpdb; $url=esc_url_raw($url);
        if(!$url || !wp_http_validate_url($url))return ['status'=>'invalid_url','http_code'=>0];
        $response=wp_safe_remote_get($url,['timeout'=>12,'redirection'=>3,'limit_response_size'=>450000,'user-agent'=>'VoyaseeBagFitSourceMonitor/'.VSB_VERSION.' (+https://voyasee.com/)']);
        $code=is_wp_error($response)?0:(int)wp_remote_retrieve_response_code($response);
        $body=is_wp_error($response)?'':(string)wp_remote_retrieve_body($response);
        $normal=preg_replace('/\s+/u',' ',wp_strip_all_tags($body)); $hash=$normal?hash('sha256',mb_substr($normal,0,300000)):'';
        $old=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.VSB_DB::source_table().' WHERE airline_id=%d',$airline_id),ARRAY_A);
        $prev=(string)($old['content_hash']??''); $changed=$prev!=='' && $hash!=='' && !hash_equals($prev,$hash);
        $status=is_wp_error($response)?'fetch_error':(($code>=200&&$code<400)?($changed?'changed_requires_review':'reachable'):'http_error');
        $row=['airline_id'=>$airline_id,'source_url'=>$url,'http_code'=>$code,'content_hash'=>$hash,'previous_hash'=>$changed?$prev:(string)($old['previous_hash']??''),'status'=>$status,'checked_at'=>current_time('mysql'),'changed_at'=>$changed?current_time('mysql'):($old['changed_at']??null),'note'=>is_wp_error($response)?sanitize_text_field($response->get_error_message()):''];
        if($old)$wpdb->update(VSB_DB::source_table(),$row,['airline_id'=>$airline_id]); else $wpdb->insert(VSB_DB::source_table(),$row);
        return ['status'=>$status,'http_code'=>$code,'changed'=>$changed];
    }
}
