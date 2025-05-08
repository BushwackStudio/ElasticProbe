/**
 * WordPress dependencies.
 */
import { safeHTML } from '@wordpress/dom';
import { createInterpolateElement, RawHTML, WPElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { useSynonymsSettings } from '../../provider';
import VisualEditor from '../editors/visual-editor';

/**
 * Replacements group component.
 *
 * @returns {WPElement}
 */
export default () => {
	const { replacements } = useSynonymsSettings();

	return (
		<>
			<RawHTML>
				{safeHTML(
					__(
						'<p><strong>Replacements</strong> are terms that replace other incorrect or obsolete terms.</p>',
						'elasticprobe',
					),
				)}
				{safeHTML(
					__(
						'<p>Use replacements when you want search queries for certain terms to return results that are only relevant to another term, or set of terms. This can be useful for supporting specific typos or incorrect phrasing. For example, when a search for the phrase "intensive purposes" should only return results including the phrase "intents and purposes".</p>',
						'elasticprobe',
					),
				)}
			</RawHTML>
			<p>
				{createInterpolateElement(
					__(
						'You may need to <a>disable fuzziness</a> to have it working properly.',
						'elasticprobe',
					),
					{
						a: (
							// eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
							<a
								target="_blank"
								href="https://www.elasticpress.io/documentation/article/how-to-disable-fuzziness/"
								rel="noreferrer"
							/>
						),
					},
				)}
			</p>
			<VisualEditor
				labels={{
					add: __('Add replacements', 'elasticprobe'),
					edit: __('Edit Replacements', 'elasticprobe'),
					new: __('Add Replacements', 'elasticprobe'),
					primary: __('Terms', 'replacements'),
					synonyms: __('Replacements', 'elasticprobe'),
				}}
				messages={{
					added: __('Added replacements.', 'elasticprobe'),
					deleted: __('Deleted replacements.', 'elasticprobe'),
					invalid: __(
						'Replacement sets require at least one term and one replacement.',
						'elasticprobe',
					),
					updated: __('Updated replacements.', 'elasticprobe'),
				}}
				mode="replacements"
				rules={replacements}
			/>
		</>
	);
};
