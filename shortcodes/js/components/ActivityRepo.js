import React, { Component } from 'react';
import PropTypes from 'prop-types';

// Internal imports.
import ActivityTable from './ActivityTable';

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
		this.fetchActivity();
	}

	fetchActivity() {
		fetch(
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
				// Loop through all the different stats we get from the endpoint, and save them in state.
				for (const data in stats) {
					const value = stats[data];
					this.setState({
						[data]: value
					});
				}
			})
			.catch((err) => {
				this.setState({
					error: err
				});
			});
	}

	render() {
		const {split_per_actor, period} = this.props;
		const {} = this.state;

		console.log(split_per_actor);
		return (
			<div>
				Nothing here just yet. I plan on adding another component to just display tables for each data set.
				<ActivityTable
					title={'This day, per actor'}
					values={this.state.actors_this_day}
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
