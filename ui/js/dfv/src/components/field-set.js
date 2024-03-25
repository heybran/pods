import React, { useMemo, useEffect } from 'react';
import PropTypes from 'prop-types';

/**
 * Pods dependencies
 */
import FieldWrapper from 'dfv/src/components/field-wrapper';
import { FIELD_PROP_TYPE_SHAPE } from 'dfv/src/config/prop-types';

const FieldSet = ( {
	storeKey,
	fields,
	podType,
	podName,
	allPodFields,
	allPodValues,
	setOptionValue,
	setOptionsValues,
} ) => {
	// Only calculate this once - this assumes that the array of all fields
	// for the Pod does not change, to save render time.
	const allPodFieldsMap = useMemo( () => {
		return new Map(
			allPodFields.map( ( fieldData ) => [ fieldData.name, fieldData ] )
		);
	}, [] );

	// When the set first mounts, apply any defaults to replace any undefined values
	// (NOT falsy values).
	useEffect( () => {
		fields.forEach( ( field ) => {
			const {
				type: fieldType,
				name: fieldName,
				boolean_group: booleanGroup = [],
			} = field;

			// Boolean Group fields need to have each subfield checked.
			const isGroupField = 'boolean_group' === fieldType;

			if ( isGroupField ) {
				booleanGroup.forEach( ( subField ) => {
					if (
						'undefined' === typeof allPodValues[ subField.name ] &&
						null !== subField?.default
					) {
						allPodValues[ subField.name ] = subField?.default ?? '';
					}
				} );
			} else if (
				'undefined' === typeof allPodValues[ fieldName ] &&
				null !== field?.default
			) {
				allPodValues[ fieldName ] = field?.default ?? '';
			}
		} );
	}, [] );

	return fields.map( ( field ) => {
		const {
			type,
			name,
			boolean_group: booleanGroup = [],
		} = field;

		const isGroupField = 'boolean_group' === type;

		// Boolean Group fields get a map of subfield values instead of a single value.
		const booleanGroupValues = {};

		if ( isGroupField ) {
			booleanGroup.forEach( ( subField ) => {
				booleanGroupValues[ subField.name ] = allPodValues[ subField.name ];
			} );
		}

		return (
			<FieldWrapper
				key={ name }
				storeKey={ storeKey }
				field={ field }
				value={ isGroupField ? undefined : allPodValues[ name ] }
				values={ isGroupField ? booleanGroupValues : undefined }
				setOptionValue={ setOptionValue }
				podType={ podType }
				podName={ podName }
				allPodFieldsMap={ allPodFieldsMap }
				allPodValues={ allPodValues }
			/>
		);
	} );
};

FieldSet.propTypes = {
	/**
	 * Redux store key.
	 */
	storeKey: PropTypes.string.isRequired,

	/**
	 * Array of fields that should be rendered in the set.
	 */
	fields: PropTypes.arrayOf( FIELD_PROP_TYPE_SHAPE ).isRequired,

	/**
	 * Pod type being edited.
	 */
	podType: PropTypes.string.isRequired,

	/**
	 * Pod slug being edited.
	 */
	podName: PropTypes.string.isRequired,

	/**
	 * All fields from the Pod, including ones that belong to other groups.
	 */
	allPodFields: PropTypes.arrayOf( FIELD_PROP_TYPE_SHAPE ).isRequired,

	/**
	 * A map object with all of the Pod's current values.
	 */
	allPodValues: PropTypes.object.isRequired,

	/**
	 * Function to update the field's value on change.
	 */
	setOptionValue: PropTypes.func.isRequired,

	/**
	 * Function to update the values of multiple options.
	 */
	setOptionsValues: PropTypes.func.isRequired,
};

export default FieldSet;
