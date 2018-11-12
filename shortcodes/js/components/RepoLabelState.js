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

	renderSection( sectionType ) {
		const { currentLabelState, previousLabelState } = this.state;
		let mainSection = 'empty';
		const title = sectionType !== 'none' ? `Open issues by ${ sectionType }` : 'Other open issues'

		const currentOpenLabelsCount = currentLabelState[1];
		const currentLabelsListForType = currentLabelState[0][ sectionType ];
		const currentLabelCounts = Object.keys( currentLabelsListForType ).length;

		if ( sectionType === 'Type' ) {
			mainSection = (
				<div>
					We have { currentOpenLabelsCount } open issues, including:
					<ul>
						<li>{ currentLabelsListForType['[Type] Bug'] } open bugs</li>
						<li>{ currentLabelsListForType['[Type] Enhancement'] } open enhancement requests</li>
					</ul>
				</div>
			)
		} else if( sectionType === 'Pri' || sectionType === 'Status' ) {
			let topLabelsSection = '';
			const type = sectionType === 'Pri' ? 'Priority' : 'Status'
			const labelTypeCount = Object.values( currentLabelsListForType ).reduce( ( acc, val ) => acc + val, 0 );
			const labelTypePercentage = Math.floor( labelTypeCount / currentOpenLabelsCount * 100 );
			if( labelTypeCount > 10 ) {
				topLabelsSection = this.renderTopIssuesSection( currentLabelsListForType, 3)
			}
			mainSection = (
				<div>
					<p>
						{ labelTypePercentage }% of the reported issues have its { type } set: { labelTypeCount } of { currentOpenLabelsCount }.
					</p>
					{ topLabelsSection }
				</div>
			)
		} else if( currentLabelCounts > 20 )  {
			const tenPercentCount = Math.floor( currentLabelCounts / 10 );
			mainSection = this.renderTopIssuesSection( currentLabelsListForType, tenPercentCount)
		} else {
			mainSection = null;
		}


		return (
			<div key={ sectionType }>
				<hr />
				<h3>{ title }</h3>
				{ mainSection }
				<div>
					{ this.renderDoughnut( currentLabelsListForType ) }
				</div>
			</div>
		)
	}

	renderTopIssuesSection( labelsList, issuesCount ) {
		let topLabels = Object.entries( labelsList );
		topLabels.sort( (a, b) => b[1] - a[1] );
		topLabels = topLabels.slice(0, issuesCount);

		return (
			<div>
				The areas with the most issues:
				<ul>
					{ topLabels.map( (labelArray, idx) => <li key={idx} >{ labelArray[0] } ({ labelArray[1] } open issues)</li>) }
				</ul>
			</div>
		)
	}

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

		let topNoneLabels = Object.entries( currentLabelState[0].none );
		topNoneLabels.sort( (a, b) => b[1] - a[1] );
		const tenPercentCount = Math.floor( topNoneLabels.length / 10 );
		topNoneLabels = topNoneLabels.slice(0, tenPercentCount)

		return (
			<div>
				{ Object.keys( currentLabelState[0] ).map( labelType => this.renderSection( labelType ) )}
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

