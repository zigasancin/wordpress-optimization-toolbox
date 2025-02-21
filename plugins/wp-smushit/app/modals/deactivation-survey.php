<?php
/**
 * Deactivation Survey Modal
 */
use Smush\Core\Helper;
$docs_link = Helper::get_utm_link(
	array(
		'utm_campaign' => 'smush_deactivation_survey_help',
	),
	'https://wpmudev.com/hub2/support/'
);
?>
<div class="<?php echo esc_attr( WP_SHARED_UI_VERSION ); ?>">
	<div class="sui-wrap">
		<div class="sui-modal sui-modal-lg">
			<div
				role="dialog"
				id="wp-smush-deactivation-survey-modal"
				class="sui-modal-content wp-smush-deactivation-survey-modal"
				aria-modal="true"
				aria-labelledby="title-wp-smush-deactivation-survey-modal"
				aria-describedby="desc-wp-smush-deactivation-survey-modal"
			>
				<div class="sui-box" role="document">
					<div class="sui-box-header">
						<h3 class="sui-box-title" style="white-space: nowrap;">
							<img style="margin-right:6px" src="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/smush-config-icon.png' ); ?>" width="30" srcset="<?php echo esc_url( WP_SMUSH_URL . 'app/assets/images/smush-config-icon.png' ); ?> 2x" alt="<?php esc_attr_e( 'Smush', 'wp-smushit' ); ?>" aria-hidden="true" />
							<?php esc_html_e( 'Deactivate Smush?', 'wp-smushit' ); ?>
						</h3>
						<div class="sui-actions-right">
							<button type="button" class="sui-button-icon" onclick="window.SUI?.closeModal( true );">
								<span class="sui-icon-close sui-md" aria-hidden="true"></span>
								<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog window', 'wp-smushit' ); ?></span>
							</button>
						</div>
					</div>
					<div class="sui-box-body">
						<p class="sui-description">
							<?php
							printf(
								/* translators: %s: Support link */
								esc_html__( 'Please tell us why. Your feedback helps us improve. %s', 'wp-smushit' ),
								WP_Smush::is_pro() ? '<a id="smush-request-assistance-link" style="text-decoration:underline" target="_blank" href="' . esc_url( $docs_link ) . '">' . esc_html__( 'Need Help?', 'wp-smushit' ) . '</a>' : ''
							);
							?>
						</p>
						<div class="smush-deactivation-field-row">
							<label for="smush-temp-deactivate-field" class="sui-radio smush-deactivation-field" data-placeholder="<?php esc_html_e( 'What issue are you debugging? (optional)', 'wp-smushit' ); ?>">
								<input
									type="radio"
									name="deactivation_reason"
									id="smush-temp-deactivate-field"
									aria-labelledby="label-smush-temp-deactivate-field"
									value="temp_deactivate"
								/>
								<span aria-hidden="true"></span>
								<span id="label-smush-temp-deactivate-field"><?php esc_html_e( 'Temporary deactivation for debugging', 'wp-smushit' ); ?></span>
							</label>
						</div>

						<div class="smush-deactivation-field-row">
							<label for="smush-not-working-field" class="sui-radio smush-deactivation-field" data-placeholder="<?php esc_html_e( 'What issue did you face? (optional)', 'wp-smushit' ); ?>">
								<input
									type="radio"
									name="deactivation_reason"
									id="smush-not-working-field"
									aria-labelledby="label-smush-not-working-field"
									value="not_working"
								/>
								<span aria-hidden="true"></span>
								<span id="label-smush-not-working-field"><?php esc_html_e( "Can't make it work", 'wp-smushit' ); ?></span>
							</label>
						</div>

						<div class="smush-deactivation-field-row">
							<label for="smush-breaks-site-field" class="sui-radio smush-deactivation-field" data-placeholder="<?php esc_html_e( 'What issue did you face? (optional)', 'wp-smushit' ); ?>">
								<input
									type="radio"
									name="deactivation_reason"
									id="smush-breaks-site-field"
									aria-labelledby="label-smush-breaks-site-field"
									value="breaks_site"
								/>
								<span aria-hidden="true"></span>
								<span id="label-smush-breaks-site-field"><?php esc_html_e( 'Breaks the site or other plugins/services', 'wp-smushit' ); ?></span>
							</label>
						</div>

						<div class="smush-deactivation-field-row">
							<label for="smush-expected-beter-field" class="sui-radio smush-deactivation-field" data-placeholder="<?php esc_html_e( 'What could we do better? (optional)', 'wp-smushit' ); ?>">
								<input
									type="radio"
									name="deactivation_reason"
									id="smush-expected-beter-field"
									aria-labelledby="label-smush-expected-beter-field"
									value="expected_better"
								/>
								<span aria-hidden="true"></span>
								<span id="label-smush-expected-beter-field"><?php esc_html_e( "Doesn't meet expectations", 'wp-smushit' ); ?></span>
							</label>
						</div>

						<div class="smush-deactivation-field-row">
							<label for="smush-found-better-field" class="sui-radio smush-deactivation-field" data-placeholder="<?php esc_html_e( 'Which plugin and how is it better? (optional)', 'wp-smushit' ); ?>">
								<input
									type="radio"
									name="deactivation_reason"
									id="smush-found-better-field"
									aria-labelledby="label-smush-found-better-field"
									value="found_better"
								/>
								<span aria-hidden="true"></span>
								<span id="label-smush-found-better-field"><?php esc_html_e( 'Found a better plugin', 'wp-smushit' ); ?></span>
							</label>
						</div>

						<div class="smush-deactivation-field-row">
							<label for="smush-not-required-field" class="sui-radio smush-deactivation-field" data-placeholder="<?php esc_html_e( 'Please tell us why. (optional)', 'wp-smushit' ); ?>">
								<input
									type="radio"
									name="deactivation_reason"
									id="smush-not-required-field"
									aria-labelledby="label-smush-not-required-field"
									value="not_required"
								/>
								<span aria-hidden="true"></span>
								<span id="label-smush-not-required-field"><?php esc_html_e( 'No longer required', 'wp-smushit' ); ?></span>
							</label>
						</div>

						<div class="smush-deactivation-field-row">
							<label for="smush-other-field" class="sui-radio smush-deactivation-field" data-placeholder="<?php esc_html_e( 'Please tell us why. (Optional)', 'wp-smushit' ); ?>">
								<input
									type="radio"
									name="deactivation_reason"
									id="smush-other-field"
									aria-labelledby="label-smush-other-field"
									value="other_issues"
								/>
								<span aria-hidden="true"></span>
								<span id="label-smush-other-field"><?php esc_html_e( 'Other', 'wp-smushit' ); ?></span>
							</label>
							<div id="smush-deactivation-user-message-field" class="sui-hidden" style="padding-left:25px; margin:10px 0;">
								<textarea
									placeholder="<?php esc_html_e( 'Please tell us why. (optional)', 'wp-smushit' ); ?>"
									class="sui-form-control"
									aria-labelledby="label-smush-deactivation-user-message"
									style="height: 40px"
									aria-describedby="error-smush-deactivation-user-message description-smush-deactivation-user-message"
								></textarea>
							</div>
						</div>
					</div>
					<div class="sui-box-footer">
						<button type="button" class="sui-button-ghost sui-button smush-skip-deactivate-button"><?php esc_html_e( 'Skip & Deactivate', 'wp-smushit' ); ?></button>
						<div class="sui-actions-right">
							<button type="button" class="sui-button-blue sui-button smush-submit-deactivate-button"><?php esc_html_e( 'Submit & Deactivate', 'wp-smushit' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
