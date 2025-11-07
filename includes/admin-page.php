<?php
/**
 * Admin page template for displaying change logs.
 *
 * @package LogChanges
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<div class="log-changes-filters">
		<form method="get" action="">
			<input type="hidden" name="page" value="log-changes" />
			
			<div class="log-changes-filter-row">
				<input type="text" 
					name="search" 
					id="log-search" 
					placeholder="<?php esc_attr_e( 'Search logs...', 'log-changes-main' ); ?>" 
					value="<?php echo isset( $_GET['search'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['search'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" 
				/>
				
				<select name="filter_action" id="filter-action">
					<option value=""><?php esc_html_e( 'All Actions', 'log-changes-main' ); ?></option>
					<?php foreach ( $action_types as $action ) : ?>
						<option value="<?php echo esc_attr( $action ); ?>" <?php selected( isset( $_GET['filter_action'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_action'] ) ) : '', $action ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>>
							<?php echo esc_html( ucfirst( $action ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				
				<select name="filter_object" id="filter-object">
					<option value=""><?php esc_html_e( 'All Object Types', 'log-changes-main' ); ?></option>
					<?php foreach ( $object_types as $object ) : ?>
						<option value="<?php echo esc_attr( $object ); ?>" <?php selected( isset( $_GET['filter_object'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_object'] ) ) : '', $object ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>>
							<?php echo esc_html( ucfirst( $object ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				
				<select name="filter_user" id="filter-user">
					<option value=""><?php esc_html_e( 'All Users', 'log-changes-main' ); ?></option>
					<?php foreach ( $users as $user ) : ?>
						<option value="<?php echo esc_attr( $user->user_id ); ?>" <?php selected( isset( $_GET['filter_user'] ) ? absint( $_GET['filter_user'] ) : 0, $user->user_id ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>>
							<?php echo esc_html( $user->user_login ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'log-changes-main' ); ?>" />
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=log-changes' ) ); ?>" class="button">
					<?php esc_html_e( 'Reset', 'log-changes-main' ); ?>
				</a>
			</div>
		</form>
	</div>
	
	<div class="log-changes-stats">
		<p>
			<?php
			/* translators: %d: number of log entries */
			printf( esc_html( _n( '%d log entry found', '%d log entries found', $total_items, 'log-changes-main' ) ), (int) $total_items );
			?>
		</p>
	</div>
	
	<?php if ( empty( $logs ) ) : ?>
		<div class="notice notice-info">
			<p><?php esc_html_e( 'No change logs found.', 'log-changes-main' ); ?></p>
		</div>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped log-changes-table">
			<thead>
				<tr>
					<th class="column-timestamp"><?php esc_html_e( 'Timestamp', 'log-changes-main' ); ?></th>
					<th class="column-user"><?php esc_html_e( 'User', 'log-changes-main' ); ?></th>
					<th class="column-action"><?php esc_html_e( 'Action', 'log-changes-main' ); ?></th>
					<th class="column-object"><?php esc_html_e( 'Object Type', 'log-changes-main' ); ?></th>
					<th class="column-description"><?php esc_html_e( 'Description', 'log-changes-main' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $logs as $log ) : ?>
					<tr class="log-entry" data-log-id="<?php echo esc_attr( $log->id ); ?>">
						<td class="column-timestamp">
							<?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log->timestamp ) ); ?>
						</td>
						<td class="column-user">
							<?php
							if ( $log->user_id ) {
								$user = get_userdata( $log->user_id );
								if ( $user ) {
									echo esc_html( $user->display_name );
								} else {
									echo esc_html( $log->user_login );
								}
							} else {
								echo '<em>' . esc_html( $log->user_login ) . '</em>';
							}
							?>
						</td>
						<td class="column-action">
							<span class="action-badge action-<?php echo esc_attr( $log->action_type ); ?>">
								<?php echo esc_html( ucfirst( $log->action_type ) ); ?>
							</span>
						</td>
						<td class="column-object">
							<span class="object-badge object-<?php echo esc_attr( $log->object_type ); ?>">
								<?php echo esc_html( ucfirst( $log->object_type ) ); ?>
							</span>
						</td>
						<td class="column-description">
							<div class="description-text">
								<?php echo esc_html( $log->description ); ?>
							</div>
							<?php if ( $log->old_value || $log->new_value ) : ?>
								<div class="log-details">
									<button type="button" class="button button-small toggle-details" data-log-id="<?php echo esc_attr( $log->id ); ?>">
										<?php esc_html_e( 'Show Details', 'log-changes-main' ); ?>
									</button>
									<div class="log-details-content" id="details-<?php echo esc_attr( $log->id ); ?>" style="display: none;">
										<?php if ( $log->old_value ) : ?>
											<div class="old-value">
												<strong><?php esc_html_e( 'Old Value:', 'log-changes-main' ); ?></strong>
												<pre><?php echo esc_html( $log->old_value ); ?></pre>
											</div>
										<?php endif; ?>
										<?php if ( $log->new_value ) : ?>
											<div class="new-value">
												<strong><?php esc_html_e( 'New Value:', 'log-changes-main' ); ?></strong>
												<pre><?php echo esc_html( $log->new_value ); ?></pre>
											</div>
										<?php endif; ?>
										<?php if ( $log->ip_address ) : ?>
											<div class="ip-address">
												<strong><?php esc_html_e( 'IP Address:', 'log-changes-main' ); ?></strong>
												<?php echo esc_html( $log->ip_address ); ?>
											</div>
										<?php endif; ?>
										<?php if ( $log->user_agent ) : ?>
											<div class="user-agent">
												<strong><?php esc_html_e( 'User Agent:', 'log-changes-main' ); ?></strong>
												<?php echo esc_html( $log->user_agent ); ?>
											</div>
										<?php endif; ?>
									</div>
								</div>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav bottom">
				<div class="tablenav-pages">
					<?php
					$page_links = paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => __( '&laquo;', 'log-changes-main' ),
							'next_text' => __( '&raquo;', 'log-changes-main' ),
							'total'     => $total_pages,
							'current'   => $page_num,
						)
					);
					
					if ( $page_links ) {
						echo '<span class="displaying-num">' . sprintf(
							/* translators: 1: current page number, 2: total pages */
							esc_html__( 'Page %1$s of %2$s', 'log-changes-main' ),
							(int) $page_num,
							(int) $total_pages
						) . '</span>';
						echo wp_kses_post( $page_links );
					}
					?>
				</div>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
