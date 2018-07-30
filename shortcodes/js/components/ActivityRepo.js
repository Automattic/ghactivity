import React, { Component } from 'react';
import PropTypes from 'prop-types';

// Internal imports.
import ActorStatsTabs from './ActorStatsTabs';

class ActivityRepo extends Component {
	constructor() {
		super();
		this.fetchActivity = this.fetchActivity.bind(this);

		this.state = {
			this_day: {},
			this_week: {},
			this_month: {},
			actors_this_day: {},
			actors_this_week: {},
			actors_this_month: {},
		};
	}

	componentDidMount() {
		return this.fetchActivity();
	}

	fetchActivity() {
		return fetch(
			`${ghactivity_repo_activity.api_url}ghactivity/v1/stats/repo/${this.props.repo}`,
			{
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': ghactivity_repo_activity.api_nonce,
					'Content-type': 'application/json' }
			}
		)
			.then(stats => stats.json())
			.then(stats => {
				let actorStats = {}
				let summaryStats = {}
				// Split data sets in two groups: by actor & summary
				Object.entries(stats).forEach( dataSet => {
					if (typeof dataSet[1] != "object") return // skip if not Object
					if (dataSet[0].startsWith('actors')) {
						actorStats[dataSet[0]] = dataSet[1]
					} else if (dataSet[0].startsWith('this_')) {
						summaryStats[dataSet[0]] = dataSet[1]
					} else {
						console.log(`Not expected dataSet: ${dataSet}`);
					}
				})
				this.setState({actorStats, summaryStats})
			})
			.catch( err => {
				console.log(err);
				this.setState({ err });
			});
	}

	render() {
		const {split_per_actor, period} = this.props;
		const { actorStats, summaryStats } = this.state

		return (
			<div className='bootstrap-iso'>
					<ActorStatsTabs
						dataSets={actorStats}
					/>
			</div>
		)
	}
}

ActivityRepo.propTypes = {
	repo: PropTypes.string.isRequired,
	split_per_actor: PropTypes.string.isRequired,
	period: PropTypes.string.isRequired
};

export default ActivityRepo;
