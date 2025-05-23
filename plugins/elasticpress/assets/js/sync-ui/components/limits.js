/**
 * WordPress dependencies.
 */
import { Flex, FlexItem, TextControl } from '@wordpress/components';
import { WPElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { useSync } from '../../sync';
import { useSyncSettings } from '../provider';

/**
 * Delete checkbox component.
 *
 * @returns {WPElement} Sync page component.
 */
export default () => {
	const { isSyncing } = useSync();
	const { args, setArgs } = useSyncSettings();

	/**
	 * Handle changing the lower limit.
	 *
	 * @param {string} value Selected lower ID.
	 * @returns {void}
	 */
	const onChangeLower = (value) => {
		const lower_limit_object_id = value ? Math.max(0, value) : value;

		setArgs({ ...args, lower_limit_object_id });
	};

	/**
	 * Handle changing the upper limit.
	 *
	 * @param {string} value Selected upper ID.
	 * @returns {void}
	 */
	const onChangeUpper = (value) => {
		const upper_limit_object_id = value ? Math.max(0, value) : value;

		setArgs({ ...args, upper_limit_object_id });
	};

	return (
		<Flex className="ep-sync-advanced-control" justify="start">
			<FlexItem grow="2">
				<TextControl
					disabled={isSyncing}
					help={__('Sync objects with an ID of this number or higher.', 'elasticpress')}
					label={__('Lower object ID', 'elasticpress')}
					max={args.upper_limit_object_id}
					min="0"
					onChange={onChangeLower}
					type="number"
					value={args.lower_limit_object_id}
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</FlexItem>
			<FlexItem grow="2">
				<TextControl
					disabled={isSyncing}
					help={__('Sync objects with an ID of this number or lower.', 'elasticpress')}
					label={__('Higher object ID', 'elasticpress')}
					min={args.lower_limit_object_id}
					onChange={onChangeUpper}
					type="number"
					value={args.upper_limit_object_id}
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			</FlexItem>
		</Flex>
	);
};
