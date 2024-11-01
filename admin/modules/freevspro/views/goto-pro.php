<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
?>
<style>
/* hide default sidebar */
.wt-gc-tab-container, .wt-gc-tab-head{ width:100%; }
.wt-gc-tab-right-container{ display:none; }

.wt_gc_freevs_pro{ width:100%; border-collapse:collapse; border-spacing:0px; margin-bottom:30px; }
.wt_gc_freevs_pro tr{ border-top:solid 1px #cacaca; }
.wt_gc_freevs_pro tr:first-child{ border-top:none; }
.wt_gc_freevs_pro td{ padding:10px 15px; padding-left:45px; text-align:left; background:#fff; color:#3A3A3A; font-weight:400; font-size:13px; }
.wt_gc_freevspro_table_hd_tr td{ padding:20px 15px; padding-left:45px; font-weight:600; text-transform:uppercase; color:#000; font-size:16px; }
.wt_gc_freevspro_table_subhd_tr td{ background:#f0f0f0; font-weight:600; font-size:14px; }
.wt_gc_freevspro_table_subhd_desc{ font-weight:400; font-size:13px; display:inline-block; margin-left:30px; }

</style>
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('.wt_gc_freevspro_table_subhd_tr').on('click', function () {
			data_index = jQuery(this).data('index');
			data_state = jQuery(this).data('state');
			if('hidden' === data_state) {
				jQuery('.wt_gc_freevspro_table_details_body' + data_index).fadeIn();
				jQuery(this).data('state', 'visible');
				jQuery(".wt_gc_freevspro_table_subhd_tr_dashicon" + data_index).removeClass("dashicons-arrow-down-alt2");
				jQuery(".wt_gc_freevspro_table_subhd_tr_dashicon" + data_index).addClass("dashicons-arrow-up-alt2");
			}else{
				jQuery('.wt_gc_freevspro_table_details_body' + data_index).hide();
				jQuery(this).data('state', 'hidden');
				jQuery(".wt_gc_freevspro_table_subhd_tr_dashicon" + data_index).removeClass("dashicons-arrow-up-alt2");
				jQuery(".wt_gc_freevspro_table_subhd_tr_dashicon" + data_index).addClass("dashicons-arrow-down-alt2");
			}
		});
	});
</script>
<div class="wt_gc_settings_left">
	<div class="wt_gc_tab_container">
		<?php
		require plugin_dir_path( __FILE__ ) . 'comparison-table.php';
		?>
	</div> 
</div>