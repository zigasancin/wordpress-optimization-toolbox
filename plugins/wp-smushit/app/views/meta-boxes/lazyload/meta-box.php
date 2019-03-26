<?php
/**
 * Lazy loading meta box.
 *
 * @since 3.2.0
 * @package WP_Smush
 *
 * @var array $settings  Lazy loading settings.
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

?>

<p>
	<?php
	esc_html_e( 'This feature defers the loading of below the fold imagery until the page has loaded. This reduces load on your server and speeds up the page load time.', 'wp-smushit' );
	?>
</p>

<div class="sui-notice sui-notice-info smush-notice-sm">
	<p><?php esc_html_e( 'Lazyloading is active.', 'wp-smushit' ); ?></p>
</div>

<form id="wp-smush-settings-form" method="post">
	<input type="hidden" name="setting_form" id="setting_form" value="lazy_load">
	<?php if ( is_multisite() && is_network_admin() ) : ?>
		<input type="hidden" name="wp-smush-networkwide" id="wp-smush-networkwide" value="1">
		<input type="hidden" name="setting-type" value="network">
	<?php endif; ?>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Media Types', 'wp-smushit' ); ?>
			</span>
			<span class="sui-description">
				<?php esc_html_e( 'Choose which media types you want to lazyload.', 'wp-smushit' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<label for="format-jpeg" class="sui-checkbox sui-checkbox-stacked">
				<input type='hidden' value='0' name='format[jpeg]' />
				<input type="checkbox" name="format[jpeg]" id="format-jpeg" <?php checked( $settings['format']['jpeg'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( '.jpeg', 'wp-smushit' ); ?></span>
			</label>
			<label for="format-png" class="sui-checkbox sui-checkbox-stacked">
				<input type='hidden' value='0' name='format[png]' />
				<input type="checkbox" name="format[png]" id="format-png" <?php checked( $settings['format']['png'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( '.png', 'wp-smushit' ); ?></span>
			</label>
			<label for="format-gif" class="sui-checkbox sui-checkbox-stacked">
				<input type='hidden' value='0' name='format[gif]' />
				<input type="checkbox" name="format[gif]" id="format-gif" <?php checked( $settings['format']['gif'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( '.gif', 'wp-smushit' ); ?></span>
			</label>
			<label for="format-svg" class="sui-checkbox sui-checkbox-stacked">
				<input type='hidden' value='0' name='format[svg]' />
				<input type="checkbox" name="format[svg]" id="format-svg" <?php checked( $settings['format']['svg'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( '.svg', 'wp-smushit' ); ?></span>
			</label>
		</div>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Output Locations', 'wp-smushit' ); ?>
			</span>
			<span class="sui-description">
				<?php esc_html_e( 'By default we will lazyload all images, but you can refine this to specific media outputs too.', 'wp-smushit' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<label for="output-content" class="sui-checkbox sui-checkbox-stacked">
				<input type='hidden' value='0' name='output[content]' />
				<input type="checkbox" name="output[content]" id="output-content" <?php checked( $settings['output']['content'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( 'Content', 'wp-smushit' ); ?></span>
			</label>
			<label for="output-widgets" class="sui-checkbox sui-checkbox-stacked">
				<input type='hidden' value='0' name='output[widgets]' />
				<input type="checkbox" name="output[widgets]" id="output-widgets" <?php checked( $settings['output']['widgets'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( 'Widgets', 'wp-smushit' ); ?></span>
			</label>
			<label for="output-thumbnails" class="sui-checkbox sui-checkbox-stacked">
				<input type='hidden' value='0' name='output[thumbnails]' />
				<input type="checkbox" name="output[thumbnails]" id="output-thumbnails" <?php checked( $settings['output']['thumbnails'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( 'Post Thumbnail', 'wp-smushit' ); ?></span>
			</label>
			<label for="output-gravatars" class="sui-checkbox sui-checkbox-stacked">
				<input type='hidden' value='0' name='output[gravatars]' />
				<input type="checkbox" name="output[gravatars]" id="output-gravatars" <?php checked( $settings['output']['gravatars'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( 'Gravatars', 'wp-smushit' ); ?></span>
			</label>
		</div>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Animation', 'wp-smushit' ); ?>
			</span>
			<span class="sui-description">
				<?php esc_html_e( 'Choose how you want to animate media when they scroll into view.', 'wp-smushit' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-side-tabs sui-tabs">
				<div data-tabs>
					<label for="animation-fadein" class="sui-tab-item <?php echo $settings['animation']['fadein'] ? 'active' : ''; ?>">
						<input type="radio" name="animation[value]" value="fadein" id="animation-fadein" <?php checked( $settings['animation']['fadein'] ); ?> />
						<?php esc_html_e( 'Fade In', 'wp-smushit' ); ?>
					</label>
					<label for="animation-spinner" class="sui-tab-item <?php echo $settings['animation']['spinner'] ? 'active' : ''; ?>">
						<input type="radio" name="animation[value]" value="spinner" id="animation-spinner" <?php checked( $settings['animation']['spinner'] ); ?> />
						<?php esc_html_e( 'Spinner', 'wp-smushit' ); ?>
					</label>
					<label for="animation-disabled" class="sui-tab-item <?php echo $settings['animation']['disabled'] ? 'active' : ''; ?>">
						<input type="radio" name="animation[value]" value="disabled" id="animation-disabled" <?php checked( $settings['animation']['disabled'] ); ?> />
						<?php esc_html_e( 'None', 'wp-smushit' ); ?>
					</label>
				</div><!-- end data-tabs -->
				<div data-panes>
					<div class="sui-tab-boxed <?php echo $settings['animation']['fadein'] ? 'active' : ''; ?>">
						<div class="sui-form-field-inline">
							<div class="sui-form-field">
								<label for="fadein-duration" class="sui-label"><?php esc_html_e( 'Duration', 'wp-smushit' ); ?></label>
								<input type='hidden' value='0' name='animation[duration]' />
								<input type="number" name="animation[duration]" placeholder="400" value="<?php echo absint( $settings['animation']['duration'] ); ?>" id="fadein-duration" class="sui-form-control sui-input-sm sui-field-has-suffix">
								<span class="sui-field-suffix"><?php esc_html_e( 'ms', 'wp-smushit' ); ?></span>
							</div>
							<div class="sui-form-field">
								<label for="fadein-delay" class="sui-label"><?php esc_html_e( 'Delay', 'wp-smushit' ); ?></label>
								<input type='hidden' value='0' name='animation[delay]' />
								<input type="number" name="animation[delay]" placeholder="0" value="<?php echo absint( $settings['animation']['delay'] ); ?>" id="fadein-delay" class="sui-form-control sui-input-sm sui-field-has-suffix">
								<span class="sui-field-suffix"><?php esc_html_e( 'ms', 'wp-smushit' ); ?></span>
							</div>
						</div>
					</div>
					<div class="sui-tab-boxed <?php echo $settings['animation']['spinner'] ? 'active' : ''; ?>" style="display:none"></div>
					<div class="sui-tab-boxed <?php echo $settings['animation']['disabled'] ? 'active' : ''; ?>" style="display:none"></div>
				</div><!-- end data-panes -->
			</div><!-- end .sui-tabs -->
		</div><!-- end .sui-box-settings-col-2 -->
	</div><!-- end .sui-box-settings-row -->

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Include / Exclude', 'wp-smushit' ); ?>
			</span>
			<span class="sui-description">
				<?php esc_html_e( 'Disable lazyloading for specific pages, posts or image classes that you wish to prevent lazyloading on.', 'wp-smushit' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<strong><?php esc_html_e( 'Post Types', 'wp-smushit' ); ?></strong>
				<div class="sui-description">
					<?php esc_html_e( 'Choose the post types you want to lazyload.', 'wp-smushit' ); ?>
				</div>
				<table class="sui-table">
					<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'wp-smushit' ); ?></th>
						<th><?php esc_html_e( 'Type', 'wp-smushit' ); ?></th>
						<th>&nbsp;</th>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Frontpage', 'wp-smushit' ); ?></strong></td>
						<td>frontpage</td>
						<td>
							<label class="sui-toggle" for="include-frontpage">
								<input type='hidden' value='0' name='include[frontpage]' />
								<input type="checkbox" name="include[frontpage]" id="include-frontpage" <?php checked( $settings['include']['frontpage'] ); ?>>
								<span class="sui-toggle-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Blog', 'wp-smushit' ); ?></strong></td>
						<td>home</td>
						<td>
							<label class="sui-toggle" for="include-home">
								<input type='hidden' value='0' name='include[home]' />
								<input type="checkbox" name="include[home]" id="include-home" <?php checked( $settings['include']['home'] ); ?>>
								<span class="sui-toggle-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Pages', 'wp-smushit' ); ?></strong></td>
						<td>page</td>
						<td>
							<label class="sui-toggle" for="include-page">
								<input type='hidden' value='0' name='include[page]' />
								<input type="checkbox" name="include[page]" id="include-page" <?php checked( $settings['include']['page'] ); ?>>
								<span class="sui-toggle-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Posts', 'wp-smushit' ); ?></strong></td>
						<td>single</td>
						<td>
							<label class="sui-toggle" for="include-single">
								<input type='hidden' value='0' name='include[single]' />
								<input type="checkbox" name="include[single]" id="include-single" <?php checked( $settings['include']['single'] ); ?>>
								<span class="sui-toggle-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Archives', 'wp-smushit' ); ?></strong></td>
						<td>archive</td>
						<td>
							<label class="sui-toggle" for="include-archive">
								<input type='hidden' value='0' name='include[archive]' />
								<input type="checkbox" name="include[archive]" id="include-archive" <?php checked( $settings['include']['archive'] ); ?>>
								<span class="sui-toggle-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Categories', 'wp-smushit' ); ?></strong></td>
						<td>category</td>
						<td>
							<label class="sui-toggle" for="include-category">
								<input type='hidden' value='0' name='include[category]' />
								<input type="checkbox" name="include[category]" id="include-category" <?php checked( $settings['include']['category'] ); ?>>
								<span class="sui-toggle-slider"></span>
							</label>
						</td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Tags', 'wp-smushit' ); ?></strong></td>
						<td>tag</td>
						<td>
							<label class="sui-toggle" for="include-tag">
								<input type='hidden' value='0' name='include[tag]' />
								<input type="checkbox" name="include[tag]" id="include-tag" <?php checked( $settings['include']['tag'] ); ?>>
								<span class="sui-toggle-slider"></span>
							</label>
						</td>
					</tr>
					</tbody>
				</table>
			</div>

			<div class="sui-form-field">
				<strong><?php esc_html_e( 'Post, Pages & URLs', 'wp-smushit' ); ?></strong>
				<div class="sui-description">
					<?php esc_html_e( 'Add URLs to the posts and/or pages you want to disable lazyloading on.', 'wp-smushit' ); ?>
				</div>
				<?php
				$strings = '';
				if ( is_array( $settings['exclude-pages'] ) ) {
					$strings = join( PHP_EOL, $settings['exclude-pages'] );
				}
				?>
				<textarea class="sui-form-control" name="exclude-pages" placeholder="<?php esc_attr_e( 'E.g. /page', 'wp-smushit' ); ?>"><?php echo esc_attr( $strings ); ?></textarea>
				<div class="sui-description">
					<?php
					printf(
						/* translators: %1$s - opening strong tag, %2$s - closing strong tag */
						esc_html__( 'Add page or post URLs one per line in relative format. I.e. %1$s/example-page%2$s or %1$s/example-page/sub-page/%2$s.', 'wp-smushit' ),
						'<strong>',
						'</strong>'
					);
					?>
				</div>
			</div>

			<div class="sui-form-field">
				<strong><?php esc_html_e( 'Classes & IDs', 'wp-smushit' ); ?></strong>
				<div class="sui-description">
					<?php esc_html_e( 'Additionally, you can specify classes or IDs to avoid lazyloading. This gives you absolute control over each image on a page, not just the page itself.', 'wp-smushit' ); ?>
				</div>
				<?php $strings = join( PHP_EOL, $settings['exclude-classes'] ); ?>
				<textarea class="sui-form-control" name="exclude-classes" placeholder="<?php esc_attr_e( 'Add classes or IDs, one per line', 'wp-smushit' ); ?>"><?php echo esc_attr( $strings ); ?></textarea>
				<div class="sui-description">
					<?php
					printf(
						/* translators: %1$s - opening strong tag, %2$s - closing strong tag */
						esc_html__( 'Add one class or ID per line, including the prefix. E.g %1$s#image-id%2$s or %1$s#image-class%2$s.', 'wp-smushit' ),
						'<strong>',
						'</strong>'
					);
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Scripts', 'wp-smushit' ); ?>
			</span>
			<span class="sui-description">
				<?php esc_html_e( 'By default we will load the required scripts in your footer for max performance benefits. If you are having issues, you can switch this to the header.', 'wp-smushit' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<strong><?php esc_attr_e( 'Method', 'wp-smushit' ); ?></strong>
				<div class="sui-description">
					<?php esc_html_e( 'By default we will load the required scripts in your footer for max performance benefits. If you are having issues, you can switch this to the header.', 'wp-smushit' ); ?>
				</div>

				<div class="sui-side-tabs sui-tabs">
					<div data-tabs>
						<label for="script-footer" class="sui-tab-item <?php echo $settings['footer'] ? 'active' : ''; ?>">
							<input type="radio" name="footer" value="on" id="script-footer" <?php checked( $settings['footer'] ); ?> />
							<?php esc_html_e( 'Footer', 'wp-smushit' ); ?>
						</label>

						<label for="script-header" class="sui-tab-item <?php echo $settings['footer'] ? '' : 'active'; ?>">
							<input type="radio" name="footer" value="off" id="script-header" <?php checked( $settings['footer'], false ); ?> />
							<?php esc_html_e( 'Header', 'wp-smushit' ); ?>
						</label>
					</div>

					<div data-panes>
						<div class="sui-notice active">
							<p><?php esc_html_e( 'Your theme must be using the wp_footer() function.', 'wp-smushit' ); ?></p>
						</div>
						<div class="sui-notice">
							<p><?php esc_html_e( 'Your theme must be using the wp_header() function.', 'wp-smushit' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Deactivate', 'wp-smushit' ); ?>
			</span>
			<span class="sui-description">
				<?php
				esc_html_e(
					'No longer wish to use this feature? Turn it off instantly by hitting Deactivate.',
					'wp-smushit'
				);
				?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<button class="sui-button sui-button-ghost" id="smush-cancel-lazyload">
				<i class="sui-icon-power-on-off" aria-hidden="true"></i>
				<?php esc_html_e( 'Deactivate', 'wp-smushit' ); ?>
			</button>
		</div>
	</div>
</form>
