import React, { Component } from 'react';
import PropTypes from 'prop-types';

import DataSetSection from './DataSetSection';

class ActivityTable extends Component {
	constructor() {
		super();
	}

	render() {
		const { title, values } = this.props;

		return (
      <table>
        <thead>
          <tr>
            <th>Type</th>
            <th>Count</th>
          </tr>
        </thead>
        <tbody>
					<DataSetSection
						dataSet={values}
						name={title}/>
				</tbody>
      </table>
    );
	}
}

ActivityTable.propTypes = {
	title: PropTypes.string.isRequired,
	values: PropTypes.object.isRequired
};

export default ActivityTable;
