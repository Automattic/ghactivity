import React, { Component } from 'react';
import PropTypes from 'prop-types';

class ActivityTable extends Component {
	constructor() {
		super();

		this.renderTable = this.renderTable.bind(this);
	}

	renderTable(key) {
		const value = this.props.values[key];

		return (
			<div key={key}>
				{value}
			</div>
		)
	}

	render() {
		const { title, values } = this.props;

		return (
			<div>
			<h3>{title}</h3>
			{Object
				.keys(values)
				.map(this.renderTable)
			}
			</div>
		)
	}
}

ActivityTable.propTypes = {
	title: PropTypes.string.isRequired,
	values: PropTypes.object.isRequired
};

export default ActivityTable;
