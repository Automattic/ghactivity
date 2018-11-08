// RepoLabelState

/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { Doughnut } from 'react-chartjs-2';
// import 'chartjs-plugin-labels'

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
		const colorArray = ['#FF6633', '#FFB399', '#FF33FF', '#FFFF99', '#00B3E6',
		'#E6B333', '#3366E6', '#999966', '#99FF99', '#B34D4D',
		'#80B300', '#809900', '#E6B3B3', '#6680B3', '#66991A',
		'#FF99E6', '#CCFF1A', '#FF1A66', '#E6331A', '#33FFCC',
		'#66994D', '#B366CC', '#4D8000', '#B33300', '#CC80CC',
		'#66664D', '#991AFF', '#E666FF', '#4DB3FF', '#1AB399',
		'#E666B3', '#33991A', '#CC9999', '#B3B31A', '#00E680',
		'#4D8066', '#809980', '#E6FF80', '#1AFF33', '#999933',
		'#FF3380', '#CCCC00', '#66E64D', '#4D80CC', '#9900B3',
		'#E64D66', '#4DB380', '#FF4D4D', '#99E6E6', '#6666FF'];
		const elementCount = Object.keys( dataObject ).length;
		const colors = colorArray.slice( 0, elementCount )
		const chartArgs = {
			labels: Object.keys( dataObject ),
			datasets: [
				{
					data: Object.values( dataObject ),
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
				labels: {
					// render: 'label',
					render: 'value',
					fontColor: '#000',
					// position: 'outside',
					// textMargin: 15,
					// outsidePadding: 10,
					// overlap: false,
					// showZero: true,
				}
			}
		}

		return <Doughnut data={ chartArgs } options={ options } />
	}

	renderSection( sectionType ) {

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


		return (
			<div>
					{ JSON.stringify( currentLabelState ) }
				{/* <div>
					{ this.renderDoughnut( currentLabelState[0].Pri ) }
				</div>
				<div>
					{ this.renderDoughnut( currentLabelState[0].Type ) }
				</div>
				<div>
					{ this.renderDoughnut( currentLabelState[0].Status ) }
				</div>
				<div>
					{ this.renderDoughnut( currentLabelState[0].none ) }
				</div> */}
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

