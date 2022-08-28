<?php

namespace Pods\Data;

use Pods\Whatsit;

/**
 * Conditional logic class.
 *
 * @since TBD
 */
class Conditional_Logic {

	/**
	 * The action to take (show/hide).
	 *
	 * @var string
	 */
	protected $action = 'show';

	/**
	 * The logic to use (any/all).
	 *
	 * @var string
	 */
	protected $logic = 'any';

	/**
	 * The conditional rules.
	 *
	 * @var array
	 */
	protected $rules = [];

	/**
	 * Set up the object.
	 *
	 * @since TBD
	 *
	 * @param string $action The action to take (show/hide).
	 * @param string $logic  The logic to use (any/all).
	 * @param array  $rules  The conditional rules.
	 */
	public function __construct( string $action, string $logic, array $rules ) {
		$this->set_action( $action );
		$this->set_logic( $logic );
		$this->set_rules( $rules );
	}

	/**
	 * Set up the conditional logic object from a object.
	 *
	 * @since TBD
	 *
	 * @param Whatsit|array $object The object data.
	 *
	 * @return Conditional_Logic|null The conditional logic object or null if rules not set.
	 */
	public static function maybe_setup_from_object( $object ): ?Conditional_Logic {
		if ( $object instanceof Whatsit && $object->is_conditional_logic_enabled() ) {
			$object_logic = $object->get_conditional_logic();

			if ( empty( $object_logic ) ) {
				return null;
			}

			return new self(
				$object_logic['action'],
				$object_logic['logic'],
				$object_logic['rules']
			);
		}

		return self::maybe_setup_from_old_syntax( $object );
	}

	/**
	 * Set up the conditional logic object from old syntax.
	 *
	 * @since TBD
	 *
	 * @param Whatsit|array $object The object data.
	 *
	 * @return Conditional_Logic|null The conditional logic object or null if rules not set.
	 */
	public static function maybe_setup_from_old_syntax( $object ): ?Conditional_Logic {
		$old_syntax = [
			'depends-on'     => (array) pods_v( 'depends-on', $object, [], true ),
			'depends-on-any' => (array) pods_v( 'depends-on-any', $object, [], true ),
			'excludes-on'    => (array) pods_v( 'excludes-on', $object, [], true ),
			'wildcard-on'    => (array) pods_v( 'wildcard-on', $object, [], true ),
		];

		$action = 'show';
		$logic  = 'any';
		$rules  = [];

		if ( $old_syntax['depends-on'] ) {
			$logic = 'all';

			foreach ( $old_syntax['depends-on'] as $field_name => $value ) {
				$rules[] = [
					'field'   => $field_name,
					'compare' => ( is_array( $value ) ? 'in' : '=' ),
					'value'   => $value,
				];
			}
		}

		if ( $old_syntax['depends-on-any'] ) {
			foreach ( $old_syntax['depends-on-any'] as $field_name => $value ) {
				$rules[] = [
					'field'   => $field_name,
					'compare' => ( is_array( $value ) ? 'in' : '=' ),
					'value'   => $value,
				];
			}
		}

		if ( $old_syntax['excludes-on'] ) {
			foreach ( $old_syntax['excludes-on'] as $field_name => $value ) {
				$rules[] = [
					'field'   => $field_name,
					'compare' => ( is_array( $value ) ? 'not in' : '!=' ),
					'value'   => $value,
				];
			}
		}

		if ( $old_syntax['wildcard-on'] ) {
			foreach ( $old_syntax['wildcard-on'] as $field_name => $value ) {
				$rules[] = [
					'field'   => $field_name,
					'compare' => 'like',
					'value'   => $value,
				];
			}
		}

		if ( empty( $rules ) ) {
			return null;
		}

		return new self( $action, $logic, $rules );
	}

	/**
	 * Get the action.
	 *
	 * @since TBD
	 *
	 * @return string The action.
	 */
	public function get_action(): string {
		return $this->action;
	}

	/**
	 * Set the action.
	 *
	 * @since TBD
	 *
	 * @param string $action The action.
	 */
	public function set_action( string $action ): void {
		if ( '' !== $action ) {
			$this->action = $action;
		}
	}

	/**
	 * Get the logic.
	 *
	 * @since TBD
	 *
	 * @return string The logic.
	 */
	public function get_logic(): string {
		return $this->logic;
	}

	/**
	 * Set the logic.
	 *
	 * @since TBD
	 *
	 * @param string $logic The logic.
	 */
	public function set_logic( string $logic ): void {
		if ( '' !== $logic ) {
			$this->logic = $logic;
		}
	}

	/**
	 * Get the conditional rules.
	 *
	 * @since TBD
	 *
	 * @return array The conditional rules.
	 */
	public function get_rules(): array {
		return $this->rules;
	}

	/**
	 * Set the conditional rules.
	 *
	 * @since TBD
	 *
	 * @param array $rules The conditional rules.
	 */
	public function set_rules( array $rules ): void {
		$this->rules = $rules;
	}

	/**
	 * Get the conditional logic data as an array.
	 *
	 * @since TBD
	 *
	 * @return array The conditional logic data as an array.
	 */
	public function to_array(): array {
		return [
			'action' => $this->action,
			'logic'  => $this->logic,
			'rules'  => $this->rules,
		];
	}

	/**
	 * Determine whether the field is visible.
	 *
	 * @since TBD
	 *
	 * @param array $values The field values.
	 *
	 * @return bool Whether the field is visible.
	 */
	public function is_field_visible( array $values ): bool {
		$rules_passed = $this->validate_rules( $values );

		if ( 'show' === $this->conditions['action'] ) {
			// Determine whether rules passed and this field should be shown.
			return $rules_passed;
		}

		// Determine whether rules passed and this field should NOT be shown.
		return ! $rules_passed;
	}

	/**
	 * Determine whether the rules validate for the field values provided.
	 *
	 * @since TBD
	 *
	 * @param array $values The field values.
	 *
	 * @return bool Whether the rules validate for the field values provided.
	 */
	public function validate_rules( array $values ): bool {
		if ( empty( $this->conditions['rules'] ) ) {
			return true;
		}

		$rules_passed     = 0;
		$rules_not_passed = 0;

		foreach ( $this->conditions['rules'] as $rule ) {
			$passed = $this->validate_rule( $rule, $values );

			if ( $passed ) {
				$rules_passed ++;
			} else {
				$rules_not_passed ++;
			}
		}

		if ( 'any' === $this->conditions['logic'] ) {
			return 0 < $rules_passed;
		}

		if ( 'all' === $this->conditions['logic'] ) {
			return 0 === $rules_not_passed;
		}

		return true;
	}

	/**
	 * Determine whether the rule validates for the field values provided.
	 *
	 * @since TBD
	 *
	 * @param array $rule   The conditional rule.
	 * @param array $values The field values.
	 *
	 * @return bool Whether the rule validates for the field values provided.
	 */
	public function validate_rule( array $rule, array $values ): bool {
		$field   = $rule['field'];
		$compare = $rule['compare'];
		$value   = $rule['value'];

		if ( empty( $field ) ) {
			return true;
		}

		$check_value = pods_v( $field, $values );

		if ( 'like' === $compare ) {
			if ( '' === $value ) {
				return true;
			}

			if ( null !== $check_value && ! is_scalar( $check_value ) ) {
				return false;
			}

			if ( null !== $value && ! is_scalar( $value ) ) {
				return false;
			}

			if ( function_exists( 'str_contains' ) ) {
				return str_contains( strtolower( (string) $check_value ), strtolower( (string) $value ) );
			}

			return false !== stripos( $check_value, $value );
		}

		if ( 'not like' === $compare ) {
			if ( '' === $value ) {
				return false;
			}

			if ( null !== $check_value && ! is_scalar( $check_value ) ) {
				return false;
			}

			if ( null !== $value && ! is_scalar( $value ) ) {
				return false;
			}

			if ( function_exists( 'str_contains' ) ) {
				return ! str_contains( strtolower( (string) $check_value ), strtolower( (string) $value ) );
			}

			return false === stripos( $check_value, $value );
		}

		if ( 'begins' === $compare ) {
			if ( '' === $value ) {
				return true;
			}

			if ( null !== $check_value && ! is_scalar( $check_value ) ) {
				return false;
			}

			if ( null !== $value && ! is_scalar( $value ) ) {
				return false;
			}

			if ( function_exists( 'str_starts_with' ) ) {
				return str_starts_with( strtolower( (string) $check_value ), strtolower( (string) $value ) );
			}

			return 0 === stripos( $check_value, $value );
		}

		if ( 'not begins' === $compare ) {
			if ( '' === $value ) {
				return false;
			}

			if ( null !== $check_value && ! is_scalar( $check_value ) ) {
				return false;
			}

			if ( null !== $value && ! is_scalar( $value ) ) {
				return false;
			}

			if ( function_exists( 'str_starts_with' ) ) {
				return ! str_starts_with( strtolower( (string) $check_value ), strtolower( (string) $value ) );
			}

			return 0 !== stripos( $check_value, $value );
		}

		if ( 'ends' === $compare ) {
			if ( '' === $value ) {
				return true;
			}

			if ( null !== $check_value && ! is_scalar( $check_value ) ) {
				return false;
			}

			if ( null !== $value && ! is_scalar( $value ) ) {
				return false;
			}

			if ( function_exists( 'str_ends_with' ) ) {
				return str_ends_with( strtolower( (string) $check_value ), strtolower( (string) $value ) );
			}

			return 0 === substr_compare( $check_value, $value, - strlen( $value ) );
		}

		if ( 'not ends' === $compare ) {
			if ( '' === $value ) {
				return false;
			}

			if ( null !== $check_value && ! is_scalar( $check_value ) ) {
				return false;
			}

			if ( null !== $value && ! is_scalar( $value ) ) {
				return false;
			}

			if ( function_exists( 'str_ends_with' ) ) {
				return ! str_ends_with( strtolower( (string) $check_value ), strtolower( (string) $value ) );
			}

			return 0 !== substr_compare( $check_value, $value, - strlen( $value ) );
		}

		if ( 'in' === $compare ) {
			if ( is_array( $check_value ) ) {
				return false;
			}

			return in_array( $check_value, (array) $value, false );
		}

		if ( 'not in' === $compare ) {
			if ( is_array( $check_value ) ) {
				return false;
			}

			return ! in_array( $check_value, (array) $value, false );
		}

		if ( is_numeric( $value ) ) {
			$value = (float) $value;
		}

		if ( is_numeric( $check_value ) ) {
			$check_value = (float) $check_value;
		}

		if ( '=' === $compare ) {
			return $check_value === $value;
		}

		if ( '!=' === $compare ) {
			return $check_value !== $value;
		}

		if ( '<' === $compare ) {
			return (float) $check_value < (float) $value;
		}

		if ( '<=' === $compare ) {
			return (float) $check_value <= (float) $value;
		}

		if ( '>' === $compare ) {
			return (float) $check_value > (float) $value;
		}

		if ( '>=' === $compare ) {
			return (float) $check_value >= (float) $value;
		}

		return false;
	}
}
