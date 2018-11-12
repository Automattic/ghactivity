// RepoLabelState

/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { Doughnut } from 'react-chartjs-2';
import 'chartjs-plugin-labels'

class RepoLabelState extends Component {
	constructor() {
		super();
		this.state = {
			records: [],
		};
	}

	componentDidMount() {
		this.fetchRepoLabelState();
	}

	fetchRepoLabelState() {
		const { repo, api_url, api_nonce } = this.props;
		return fetch(
			`${ api_url }ghactivity/v1/queries/repo-label-state/repo/${ repo }`,
			{
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': api_nonce,
					'Content-type': 'application/json' },
			}
		)
			.then( data => data.json() )
			.then( data => {
				this.setState( {
					currentLabelState: data.current_label_state,
					previousLabelState: data.previous_label_state,
				} );
			} )
			.catch( err => {
				console.log( err );
				this.setState( { err } );
			} );
	}

	// data.current_label_state[0].Pri
	renderDoughnut(dataObject) {
		const dataEntries = Object.entries( dataObject );
		dataEntries.sort( (a, b) => a[1] - b[1] );

		const labels = dataEntries.map( e => e[0] );
		const data = dataEntries.map( e => e[1] );
		const elementCount = labels.length;

		const colors = this.getRandomColors( elementCount )
		const chartArgs = {
			labels,
			datasets: [
				{
					data,
					backgroundColor: colors,
					hoverBackgroundColor: colors,
				},
			],
		};

		const options = {
			legend: {
				display: false,
			},
			plugins: {
				labels: [
					{
						render: args => args.percentage < 2 ? '' : args.label,
						fontColor: '#000',
						position: 'outside',
						textMargin: 20,
						outsidePadding: 20,
					},
					{
						render: args => args.percentage < 2 ? '' : args.value,
						fontColor: '#000',
					}
				]
			}
		}

		return <Doughnut data={ chartArgs } options={ options } />
	}

	// renderSection( sectionType ) {
	// 	const { currentLabelState, previousLabelState } = this.state;
	// }

	// https://stackoverflow.com/a/1484514/3078381
	getRandomColors( count ) {
		const colors = [];
		const letters = '0123456789ABCDEF';
		for ( let c = 0; c < count; c++ ) {
			let color = '#';
			for ( let i = 0; i < 6; i++ ) {
				color += letters[Math.floor(Math.random() * 16)];
			}
			colors.push(color);
		}
		return colors;
	}

	render() {
		const { currentLabelState, previousLabelState } = this.state;

		if ( ! currentLabelState || currentLabelState.length === 0 ) {
			return (
				<div>
					Loading...
				</div>
			)
		}

		const priLabeledCount = Object.values( currentLabelState[0].Pri ).reduce( ( acc, val ) => acc + val, 0 );
		const priLabeledPercentage = Math.floor( priLabeledCount / currentLabelState[1] * 100 );

		let topNoneLabels = Object.entries( currentLabelState[0].none );
		topNoneLabels.sort( (a, b) => b[1] - a[1] );
		const tenPercentCount = Math.floor( topNoneLabels.length / 10 );
		topNoneLabels = topNoneLabels.slice(0, tenPercentCount)

		return (
			<div>
				<hr />
				{/* Type Section */}
				<div>
					<p>
						We have { currentLabelState[1] } open issues, including:
					</p>
					<ul>
						<li>{ currentLabelState[0].Type['[Type] Bug'] } open bugs</li>
						<li>{ currentLabelState[0].Type['[Type] Enhancement'] } open enhancement requests</li>
					</ul>
					<div>
						{ this.renderDoughnut( currentLabelState[0].Type ) }
					</div>
				</div>
				<hr />
				{/* Priority Section */}
				<div>
					<p>
						{ priLabeledPercentage }% of the reported issues have its priority set: { priLabeledCount } of { currentLabelState[1] }. These are broken down as:
					</p>
					<div>
						{ this.renderDoughnut( currentLabelState[0].Pri ) }
					</div>
				</div>
				<hr />
				{/* none Section */}
				<div>
					<p>
					The areas with the most issues:
					</p>
					<ul>
						{ topNoneLabels.map( (labelArray, idx) => <li key={idx} >{labelArray[0]} ({labelArray[1]} open issues)</li>) }
					</ul>
					<div>
						{ this.renderDoughnut( currentLabelState[0].none ) }
					</div>
				</div>
			</div>

		);
	}
}

RepoLabelState.propTypes = {
	repo: PropTypes.string.isRequired,
	api_url: PropTypes.string.isRequired,
	api_nonce: PropTypes.string.isRequired,
};

export default RepoLabelState;

