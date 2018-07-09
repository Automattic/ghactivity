import React from 'react';
import { render } from 'react-dom';
import 'bootstrap';
import '../../css/bootstrap/css/bootstrap-iso.css';

// Internal imports.
import ActivityRepo from './components/ActivityRepo';

render((
	<ActivityRepo
		repo={ghactivity_repo_activity.repo}
		split_per_actor={ghactivity_repo_activity.split_per_actor}
		period={ghactivity_repo_activity.period}
	/>
), document.querySelector( '#repo-activity' ) );
