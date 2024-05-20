import React, { useState, useEffect, useMemo, useCallback, useRef } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Modal, Button, Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import DynamicTabContent from './dynamic-tab-content';
import sanitizeSlug from 'dfv/src/helpers/sanitizeSlug';
import { validateFieldDependencies, formatDependency } from 'dfv/src/helpers/validateFieldDependencies';
import { toBool } from 'dfv/src/helpers/booleans';

import './settings-modal.scss';

const ENTER_KEY = 13;

/**
 * Helper function to check the validity of the form.
 *
 * @param {Array} sections Array of sections, each one having the structure
 *                         of a "group" containing an array of fields.
 * @param {Object} options Key/value of options to apply.
 */
const checkFormValidity = ( sections, options ) => {
	// Go through each section, check that each one has all valid fields.
	return sections.every(
		( section ) => {
			let {
				fields,
				'depends-on': dependsOn,
				'depends-on-any': dependsOnAny,
				'excludes-on': excludesOn,
				'wildcard-on': wildcardOn,
			} = section;

			dependsOn = formatDependency( dependsOn );
			dependsOnAny = formatDependency( dependsOnAny );
			excludesOn = formatDependency( excludesOn );
			wildcardOn = formatDependency( wildcardOn );

			// Skip the section if it doesn't have any fields.
			if ( ! fields || 0 === fields.length ) {
				return true;
			}

			// Skip the section if it isn't being shown because it's dependencies aren't met.
			if ( Object.keys( dependsOn || {} ).length && ! validateFieldDependencies( options, dependsOn ) ) {
				return true;
			}

			if ( Object.keys( dependsOnAny || {} ).length && ! validateFieldDependencies( options, dependsOnAny, 'depends-on-any' ) ) {
				return true;
			}

			if ( Object.keys( excludesOn || {} ).length && ! validateFieldDependencies( options, excludesOn, 'excludes-on' ) ) {
				return true;
			}

			if ( Object.keys( wildcardOn || {} ).length && ! validateFieldDependencies( options, wildcardOn, 'wildcard-on' ) ) {
				return true;
			}

			// If we haven't skipped the section, look through each field.
			return fields.every(
				( field ) => {
					const {
						required: fieldRequired,
						'depends-on': fieldDependsOn,
						'depends-on-any': fieldDependsOnAny,
						'excludes-on': fieldExcludesOn,
						'wildcard-on': fieldWildcardOn,
						type: fieldType,
						name: fieldName,
					} = field;

					// Fields that aren't required are automatically valid.
					if ( undefined === typeof fieldRequired || ! toBool( fieldRequired ) ) {
						return true;
					}

					// Skip the fields if it isn't being shown because it's dependencies aren't met.
					if ( Object.keys( fieldDependsOn || {} ).length && ! validateFieldDependencies( options, fieldDependsOn ) ) {
						return true;
					}

					if ( Object.keys( fieldDependsOnAny || {} ).length && ! validateFieldDependencies( options, fieldDependsOn, 'depends-on-any' ) ) {
						return true;
					}

					if ( Object.keys( fieldExcludesOn || {} ).length && ! validateFieldDependencies( options, fieldExcludesOn, 'excludes-on' ) ) {
						return true;
					}

					if ( Object.keys( fieldWildcardOn || {} ).length && ! validateFieldDependencies( options, fieldWildcardOn, 'wildcard-on' ) ) {
						return true;
					}

					// Boolean values could be falsey and still valid.
					if (
						'boolean' === fieldType &&
						'undefined' !== typeof options[ fieldName ]
					) {
						return true;
					}

					// If the option's value is not undefined, and not an empty
					// string, return true;
					if (
						'undefined' !== typeof options[ fieldName ] &&
						'' !== options[ fieldName ].toString()
					) {
						return true;
					}

					return false;
				}
			);
		}
	);
};

const SettingsModal = ( {
	storeKey,
	title,
	optionsPod: {
		groups: optionsSections = [],
	} = {},
	isSaving,
	hasSaveError,
	saveButtonText,
	errorMessage,
	selectedOptions,
	cancelEditing,
	save,
} ) => {
	const [ selectedTab, setSelectedTab ] = useState( optionsSections[ 0 ].name );

	const [ changedOptions, setChangedOptions ] = useState( selectedOptions );

	const [ isValid, setIsValid ] = useState( false );

	const [ hasUnsavedChanges, setHasUnsavedChanges ] = useState( false );

	const tabContentRef = useRef( null );

	const ignoreOptions = [];

	const changeTab = ( tabName ) => {
		setSelectedTab( tabName );

		if ( tabContentRef.current ) {
			tabContentRef.current.scrollTo( 0, 0 );
		}
	};

	const handleCancel = ( event ) => {
		if ( ! hasUnsavedChanges ) {
			cancelEditing( event );

			return;
		}

		// eslint-disable-next-line no-alert
		const result = confirm(
			__( 'There are unsaved changes that will be lost. Are you sure you want to discard those changes and close?', 'pods' ),
		);

		if ( result ) {
			cancelEditing( event );
		}
	};

	const handleSave = ( event ) => {
		save( changedOptions )( event );
	};

	// Wrapper around setChangedOptions(), which also sets the name/slug
	// based on the Label, if the slug hasn't previously been set.
	const setOptionValue = ( optionName, value ) => {
		// Do nothing if this option was ignored.
		if ( ignoreOptions.includes( optionName ) ) {
			return;
		}

		const newOptions = {
			[ optionName ]: value,
		};

		// Generate a slug if needed.
		if ( 'label' === optionName && ( 'undefined' === typeof selectedOptions.name || '' === selectedOptions.name.trim() ) ) {
			newOptions.name = sanitizeSlug( value );
		}

		setChangedOptions( ( previousChangedOptions ) => ( {
			...previousChangedOptions,
			...newOptions,
		} ) );

		setHasUnsavedChanges( true );
	};
	const setOptionValueCallback = useCallback( setOptionValue, [] );

	// When the modal first opens, set any options to their defaults, unless
	// they're already set. This will need to happen again when any option changes,
	// because a new option may reveal fields with dependencies that were previously unset.
	useEffect( () => {
		const defaultOptions = {};

		optionsSections.forEach( ( optionsSection ) => {
			if ( ! optionsSection.fields.length ) {
				return;
			}

			optionsSection.fields.forEach( ( field ) => {
				// If this is a field type that should not have values, do not store them or use them.
				if ( 'boolean_group' === field.type || window.podsDFVConfig.fieldTypeInfo.layout.includes( field.type ) || window.podsDFVConfig.fieldTypeInfo.non_input.includes( field.type ) ) {
					return;
				}

				// Don't overwrite values that we already took from the changedOptions.
				if ( 'undefined' !== typeof changedOptions[ field.name ] ) {
					return;
				}

				// Only set the value if it wasn't previously supplied,
				// and only if a default is provided. Unless it's a select
				// menu, then go ahead and set the first option.
				if (
					'undefined' !== typeof field.default &&
					'' !== field.default
				) {
					defaultOptions[ field.name ] = field.default;
				} else if ( ( 'pick' === field.type ) && field.data ) {
					// A select menu could have data with just key/values,
					// or it could have option groups. If the first option is
					// a string, use it, but if it's an object, find the first option
					// in the option group.
					const pickKeys = Object.keys( field.data );

					if ( 'object' === typeof field?.data[ pickKeys[ 0 ] ] ) {
						const firstOptionGroup = field.data[ pickKeys[ 0 ] ];

						const firstOptionGroupKeys = Object.keys( firstOptionGroup );

						if ( firstOptionGroupKeys.length ) {
							defaultOptions[ field.name ] = firstOptionGroupKeys[ 0 ];
						}
					} else if ( 'string' === typeof field?.data[ pickKeys[ 0 ] ] ) {
						defaultOptions[ field.name ] = field.data[ pickKeys[ 0 ] ];
					}
				}
			} );
		} );

		// Update changed options, but give priority to
		// any changedOptions that already existed.
		setChangedOptions( ( prevChangedOptions ) => ( {
			...defaultOptions,
			...prevChangedOptions,
		} ) );
	}, [ setChangedOptions ] );

	// Check validity if any of the options have changed.
	useEffect( () => {
		const validity = checkFormValidity( optionsSections, changedOptions );

		setIsValid( validity );
	}, [ changedOptions, setIsValid ] );

	const allPodFields = useMemo(
		() => optionsSections.reduce(
			( accumulator, group ) => ( [
				...accumulator,
				...( group?.fields || [] ),
			] ),
			[]
		),
		[ optionsSections ],
	);

	return (
		<Modal
			className="pods-settings-modal"
			title={ title }
			isDismissible={ true }
			onRequestClose={ handleCancel }
			focusOnMount={ true }
			shouldCloseOnClickOutside={ false }
		>
			{ hasSaveError && (
				<div className="pod-field-group_settings-error-message">
					{ errorMessage }
				</div>
			) }

			<div className="pods-settings-modal__container">
				<div
					className="pods-settings-modal__tabs"
					role="tablist"
					aria-label={ __( 'Pods Field Group Settings', 'pods' ) }
				>
					{ optionsSections.map( ( {
						name: sectionName,
						label: sectionLabel,
						'depends-on': dependsOn = {},
						'excludes-on': excludesOn = {},
						'wildcard-on': wildcardOn = {},
						fields,
					} ) => {
						// Hide any sections that are missing fields.
						if ( ! fields.length ) {
							return null;
						}

						// Check that dependencies are met.
						if ( Object.keys( dependsOn || {} ).length && ! validateFieldDependencies( changedOptions, dependsOn ) ) {
							return null;
						}

						// Check that exclusions are met.
						if ( Object.keys( excludesOn || {} ).length && ! validateFieldDependencies( changedOptions, excludesOn, 'excludes-on' ) ) {
							return null;
						}

						// Check that wildcard dependencies are met.
						if ( Object.keys( wildcardOn || {} ).length && ! validateFieldDependencies( changedOptions, wildcardOn, 'wildcard-on' ) ) {
							return null;
						}

						const isActive = selectedTab === sectionName;

						const classes = classNames(
							'pods-settings-modal__tab-item',
							isActive && 'pods-settings-modal__tab-item--active',
						);

						return (
							<div
								className={ classes }
								aria-controls={ `${ sectionName }-tab` }
								role="button"
								tabIndex={ 0 }
								key={ sectionName }
								onClick={ () => changeTab( sectionName ) }
								onKeyPress={ ( event ) => event.charCode === ENTER_KEY && changeTab( sectionName ) }
							>
								{ sectionLabel }
							</div>
						);
					} ) }
				</div>

				<div
					className="pods-settings-modal__panel"
					role="tabpanel"
					aria-labelledby="main"
					id="main-tab"
					ref={ tabContentRef }
				>
					{
						<DynamicTabContent
							storeKey={ storeKey }
							tabOptions={ optionsSections.find( ( section ) => section.name === selectedTab )?.fields }
							allPodFields={ allPodFields }
							allPodValues={ changedOptions }
							setOptionValue={ setOptionValueCallback }
						/>
					}
				</div>
			</div>

			<div className="pods-setting-modal__button-group">
				{ isSaving && <Spinner /> }

				<Button
					isSecondary
					onClick={ handleCancel }
					disabled={ isSaving }
					aria-disabled={ isSaving }
					aria-label={ __( 'Cancel the modal changes', 'pods' ) }
				>
					{ __( 'Cancel', 'pods' ) }
				</Button>

				<Button
					isPrimary
					onClick={ handleSave }
					disabled={ ! isValid || isSaving }
					aria-disabled={ ! isValid || isSaving }
				>
					{ saveButtonText }
				</Button>
			</div>
		</Modal>
	);
};

SettingsModal.propTypes = {
	storeKey: PropTypes.string.isRequired,
	optionsPod: PropTypes.object.isRequired,
	selectedOptions: PropTypes.object.isRequired,
	title: PropTypes.string.isRequired,
	isSaving: PropTypes.bool.isRequired,
	hasSaveError: PropTypes.bool.isRequired,
	errorMessage: PropTypes.string.isRequired,
	saveButtonText: PropTypes.string.isRequired,
	cancelEditing: PropTypes.func.isRequired,
	save: PropTypes.func.isRequired,
};

export default SettingsModal;
