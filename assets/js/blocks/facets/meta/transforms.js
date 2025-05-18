/**
 * WordPress dependencies.
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Filter by Metadata block transforms.
 */
export default {
	from: [
		{
			type: 'block',
			blocks: ['elasticprobe/facet-meta-range'],
			transform: (props) => {
				return createBlock('elasticprobe/facet-meta', props);
			},
		},
	],
};
