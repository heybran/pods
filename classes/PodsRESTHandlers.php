<?php

/**
 * Class PodsRESTHandlers
 *
 * Handlers for reading and writing Pods fields via REST API
 *
 * @package Pods
 * @since   2.5.6
 */
class PodsRESTHandlers {

	/**
	 * Holds a Pods object to avoid extra DB queries
	 *
	 * @since 2.5.6
	 *
	 * @var Pods
	 */
	private static $pods = false;

	/**
	 * Get the Pods object.
	 *
	 * @since 2.5.6
	 *
	 * @param string     $pod_name The pod name.
	 * @param string|int $id       The item ID.
	 *
	 * @return bool|Pods The Pods object or false if not found.
	 */
	public static function get_pods_object( $pod_name, $id ) {

		if ( ! self::$pods || self::$pods->pod !== $pod_name ) {
			self::$pods = pods_get_instance( $pod_name, $id, true );
		}

		if ( self::$pods ) {
			if ( (int) self::$pods->id !== (int) $id ) {
				self::$pods->fetch( $id );
			}

			if ( ! self::$pods->exists() ) {
				return false;
			}
		}

		return self::$pods;

	}

	/**
	 * Handler for getting custom field data.
	 *
	 * @since 2.5.6
	 *
	 * @param array           $object      The object from the response
	 * @param string          $field_name  Name of field
	 * @param WP_REST_Request $request     Current request
	 * @param string          $object_type Type of object
	 *
	 * @return mixed
	 */
	public static function get_handler( $object, $field_name, $request, $object_type ) {

		$pod_name = pods_v( 'type', $object );

		/**
		 * If $pod_name in the line above is empty then the route invoked
		 * may be for a taxonomy, so lets try and check for that
		 */
		if ( empty( $pod_name ) ) {
			$pod_name = pods_v( 'taxonomy', $object );
		}

		/**
		 * $pod_name is still empty, so check lets check $object_type
		 */

		if ( empty( $pod_name ) ) {
			$pod_name = $object_type;
		}

		// Fix media pod name.
		if ( 'attachment' === $pod_name ) {
			$pod_name = 'media';
		}

		/**
		 * Filter the pod name for the REST API handler.
		 *
		 * @since 2.6.7
		 *
		 * @param array           $pod_name    The Pod name.
		 * @param array           $object      The REST object.
		 * @param string          $field_name  The name of the field.
		 * @param WP_REST_Request $request     The current request.
		 * @param string          $object_type The REST object type.
		 */
		$pod_name = apply_filters( 'pods_rest_api_pod_name', $pod_name, $object, $field_name, $request, $object_type );

		$id = pods_v( 'id', $object );

		if ( empty( $id ) ) {
			$id = pods_v( 'ID', $object );
		}

		$pod = self::get_pods_object( $pod_name, $id );

		$value = false;

		if ( $pod && PodsRESTFields::field_allowed_to_extend( $field_name, $pod, 'read' ) ) {
			$field_mode = pods_v( 'rest_api_field_mode', $pod->pod_data, 'value', true );

			// Only deal with raw values if not in rendered field mode.
			if ( 'rendered' !== $field_mode ) {
				$params = null;

				$field_data = $pod->fields( $field_name );

				if ( 'pick' === pods_v( 'type', $field_data ) ) {
					$output_type = pods_v( 'rest_pick_response', $field_data, 'array' );

					/**
					 * What output type to use for a related field REST response.
					 *
					 * @since 2.7.0
					 *
					 * @param string                 $output_type The pick response output type.
					 * @param string                 $field_name  The name of the field
					 * @param array                  $field_data  The field data
					 * @param object|Pods            $pod         The Pods object for Pod relationship is from.
					 * @param int                    $id          Current item ID
					 * @param object|WP_REST_Request $request     Current request object.
					 */
					$output_type = apply_filters( 'pods_rest_api_output_type_for_relationship_response', $output_type, $field_name, $field_data, $pod, $id, $request );

					if ( 'custom' === $output_type ) {
						// Support custom selectors for the response.
						$custom_selector = pods_v( 'rest_pick_custom', $field_data, $field_name );

						if ( ! empty( $custom_selector ) ) {
							$field_name = $custom_selector;
						}
					} elseif ( 'array' === $output_type ) {
						// Support fully fleshed out data for the response.
						$related_pod_items = $pod->field( $field_name, array( 'output' => 'pod' ) );

						if ( $related_pod_items ) {
							$fields = false;
							$items  = array();
							$depth  = (int) pods_v( 'rest_pick_depth', $field_data, 2 );

							if ( ! is_array( $related_pod_items ) ) {
								$related_pod_items = array( $related_pod_items );
							}

							/**
							 * @var $related_pod Pods
							 */
							foreach ( $related_pod_items as $related_pod ) {
								if ( ! is_object( $related_pod ) || ! $related_pod instanceof Pods ) {
									$items = $related_pod_items;

									break;
								}

								if ( false === $fields ) {
									$fields = pods_config_get_all_fields( $related_pod );
									$fields = array_keys( $fields );

									/**
									 * What fields to show in a related field REST response.
									 *
									 * @since 0.0.1
									 *
									 * @param array                  $fields      The fields to show
									 * @param string                 $field_name  The name of the field
									 * @param object|Pods            $pod         The Pods object for Pod relationship is from.
									 * @param object|Pods            $related_pod The Pods object for Pod relationship is to.
									 * @param int                    $id          Current item ID
									 * @param object|WP_REST_Request $request     Current request object.
									 */
									$fields = apply_filters( 'pods_rest_api_fields_for_relationship_response', $fields, $field_name, $pod, $related_pod, $id, $request );
								}//end if

								/**
								 * What depth to use for a related field REST response.
								 *
								 * @since 0.0.1
								 *
								 * @param int                    $depth      The depth number to limit to.
								 * @param string                 $field_name  The name of the field
								 * @param object|Pods            $pod         The Pods object for Pod relationship is from.
								 * @param object|Pods            $related_pod The Pods object for Pod relationship is to.
								 * @param int                    $id          Current item ID
								 * @param object|WP_REST_Request $request     Current request object.
								 */
								$related_depth = (int) apply_filters( 'pods_rest_api_depth_for_relationship_response', $depth, $field_name, $pod, $related_pod, $id, $request );

								$params = array(
									'fields'  => $fields,
									'depth'   => $related_depth,
									'context' => 'rest',
								);

								$items[] = $related_pod->export( $params );
							}//end foreach

							$value = $items;
						}//end if
					}//end if

					$params = array(
						'output' => $output_type,
					);
				}//end if

				// If no value set yet, get normal field value
				if ( ! $value && ! is_array( $value ) ) {
					$value = $pod->field( $field_name, $params );
				}
			}

			// Handle other field modes.
			if ( 'value_and_render' === $field_mode ) {
				$value = [
					'value'    => $value,
					'rendered' => $pod->display( $field_name ),
				];
			} elseif ( 'render' === $field_mode ) {
				$value = $pod->display( $field_name );
			}
		}//end if

		return $value;

	}

	/**
	 * Handle saving of Pod fields from REST API write requests.
	 *
	 * @param WP_Post|WP_Term|WP_User|WP_Comment $object   Inserted or updated object.
	 * @param WP_REST_Request                    $request  Request object.
	 * @param bool                               $creating True when creating an item, false when updating.
	 */
	public static function save_handler( $object, $request, $creating ) {

		if ( $object instanceof WP_Post ) {
			$type = $object->post_type;

			$id = $object->ID;
		} elseif ( $object instanceof WP_Term ) {
			$type = $object->taxonomy;

			$id = $object->term_id;
		} elseif ( $object instanceof WP_User ) {
			$type = 'user';

			$id = $object->ID;
		} elseif ( $object instanceof WP_Comment ) {
			$type = 'comment';

			$id = $object->comment_ID;
		} else {
			// Not a supported object
			return;
		}//end if

		$pod_name = $type;

		if ( 'attachment' === $type && $object instanceof WP_Post ) {
			$pod_name = 'media';
		}

		$pod = self::get_pods_object( $pod_name, $id );

		global $wp_rest_additional_fields;

		$rest_enable = (boolean) pods_v( 'rest_enable', $pod->pod_data, false );

		if ( $pod && $rest_enable && ! empty( $wp_rest_additional_fields[ $type ] ) ) {
			$fields = $pod->fields();

			$save_fields = array();

			$params = array(
				'is_new_item' => $creating,
			);

			foreach ( $fields as $field_name => $field ) {
				if ( empty( $wp_rest_additional_fields[ $type ][ $field_name ]['pods_update'] ) ) {
					continue;
				} elseif ( ! isset( $request[ $field_name ] ) ) {
					continue;
				} elseif ( ! PodsRESTFields::field_allowed_to_extend( $field_name, $pod, 'write' ) ) {
					continue;
				}

				$save_fields[ $field_name ] = $request[ $field_name ];
			}

			if ( ! empty( $save_fields ) || $creating ) {
				$pod->save( $save_fields, null, null, $params );
			}
		}//end if

	}

}
