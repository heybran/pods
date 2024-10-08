<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * @var Pods $pod
 * @var array<string,\Pods\Whatsit\Field> $fields
 * @var string $action
 * @var string $search
 * @var string $label
 */
?>
<form method="get" class="pods-form-filters pods-form-filters-<?php echo esc_attr( $pod->pod ); ?>" action="<?php echo esc_attr( $action ); ?>">
	<?php
	foreach ( $fields as $name => $field ) {
		if ( in_array( $field['type'], array( 'pick', 'taxonomy' ), true ) && 'pick-custom' !== $field['pick_object'] && ! empty( $field['pick_object'] ) ) {
			$field['pick_format_type']             = 'single';
			$field['pick_format_single']           = 'dropdown';
			$field['pick_select_text']             = '-- ' . $field['label'] . ' --';
			$field['pick_show_select_text']        = 1;
			$field['pick_select_text_always_show'] = 1;

			$filter = sanitize_text_field( pods_v( $pod->filter_var . '_' . $name, 'get', '' ) );

			// @todo Support other field types.
			$field['type'] = 'pick';

			echo PodsForm::field( $pod->filter_var . '_' . $name, $filter, $field['type'], $field, $pod->pod, $pod->id() );
		}
	}
	?>

	<input type="text" class="pods-form-filters-search" name="<?php echo esc_attr( $pod->search_var ); ?>" value="<?php echo esc_attr( $search ); ?>" />

	<input type="submit" class="pods-form-filters-submit" value="<?php echo esc_attr( $label ); ?>" />
</form>
