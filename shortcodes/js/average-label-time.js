/**
 * External dependencies
 */
import React from 'react';
import { render } from 'react-dom';

/**
 * Internal dependencies
 */
import AverageLabelTime from './components/AverageLabelTime';

render((
	<AverageLabelTime
		repo={ ghactivity_avg_label_time.repo }
		label={ ghactivity_avg_label_time.label }
	/>
), document.querySelector( '#avg-label-time' ) );
