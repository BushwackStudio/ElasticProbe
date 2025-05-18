/**
 * WordPress dependencies.
 */
import { createBlock } from '@wordpress/blocks';

/**
 * Filter by Metadata Range block transforms.
 */
export default {
	from: [
		{
			type: 'block',
			blocks: ['elasticprobe/facet-meta'],
			transform: (props) => {
				return createBlock('elasticprobe/facet-meta-range', props);
			},
		},
	],
};
