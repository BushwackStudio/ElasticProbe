/**
 * WordPress dependencies.
 */
import { Button } from '@wordpress/components';
import { WPElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { useSync } from '../../sync';
import { useSyncSettings } from '../provider';

/**
 * Sync controls component.
 *
 * @returns {WPElement} Component.
 */
export default () => {
	const { isPaused, isSyncing, logMessage, pauseSync, resumeSync, stopSync } = useSync();

	const { args } = useSyncSettings();

	/**
	 * Handle clicking pause button.
	 *
	 * @returns {void}
	 */
	const onPause = () => {
		pauseSync();
		logMessage(__('Pausing sync…', 'elasticprobe'), 'info');
	};

	/**
	 * Handle clicking play button.
	 *
	 * @returns {void}
	 */
	const onResume = () => {
		resumeSync(args);
		logMessage(__('Resuming sync…', 'elasticprobe'), 'info');
	};

	/**
	 * Handle clicking stop button.
	 *
	 * @returns {void}
	 */
	const onStop = () => {
		stopSync();
		logMessage(__('Sync stopped', 'elasticprobe'), 'info');
	};

	/**
	 * Render.
	 */
	return (
		<div className="ep-sync-controls">
			{isSyncing ? (
				<>
					<Button onClick={onStop} variant="primary">
						{__('Stop sync', 'elasticprobe')}
					</Button>
					{isPaused ? (
						<Button onClick={onResume} variant="secondary">
							{__('Resume sync', 'elasticprobe')}
						</Button>
					) : (
						<Button onClick={onPause} variant="secondary">
							{__('Pause sync', 'elasticprobe')}
						</Button>
					)}
				</>
			) : (
				<Button variant="primary" type="submit">
					{__('Start sync', 'elasticprobe')}
				</Button>
			)}
			<Button
				href="https://www.elasticpress.io/documentation/article/what-is-the-elasticpress-sync/"
				target="_blank"
				variant="link"
			>
				{__('Learn more about Sync', 'elasticprobe')}
			</Button>
		</div>
	);
};
