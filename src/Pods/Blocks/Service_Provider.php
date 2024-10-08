<?php

namespace Pods\Blocks;

use Pods\Blocks\Collections\Pods;
use Pods\Blocks\Types\Field;
use Pods\Blocks\Types\Form;
use Pods\Blocks\Types\Item_List;
use Pods\Blocks\Types\Item_Single;
use Pods\Blocks\Types\Item_Single_List_Fields;
use Pods\Blocks\Types\Related_Item_List;
use Pods\Blocks\Types\View;

/**
 * Class Service_Provider
 *
 * Add Blocks integration.
 *
 * @since 2.8.0
 */
class Service_Provider extends \Pods\Service_Provider_Base {

	/**
	 * Registers the classes and functionality needed for the Blocks API.
	 *
	 * @since 2.8.0
	 */
	public function register() {
		$this->container->singleton( 'pods.blocks', API::class );
		$this->container->singleton( 'pods.blocks.collection.pods', Pods::class, [ 'register_with_pods' ] );

		// To add new blocks, also update \Pods\Blocks\API::setup_core_blocks() to call the block.
		$this->container->singleton( 'pods.blocks.field', Field::class, [ 'register_with_pods' ] );
		$this->container->singleton( 'pods.blocks.form', Form::class, [ 'register_with_pods' ] );
		$this->container->singleton( 'pods.blocks.list', Item_List::class, [ 'register_with_pods' ] );
		$this->container->singleton( 'pods.blocks.related-list', Related_Item_List::class, [ 'register_with_pods' ] );
		$this->container->singleton( 'pods.blocks.single', Item_Single::class, [ 'register_with_pods' ] );
		$this->container->singleton( 'pods.blocks.single-list-fields', Item_Single_List_Fields::class, [ 'register_with_pods' ] );
		$this->container->singleton( 'pods.blocks.view', View::class, [ 'register_with_pods' ] );

		$this->hooks();
	}

	/**
	 * Hooks all the methods and actions the class needs.
	 *
	 * @since 2.8.0
	 */
	protected function hooks() {
		add_action( 'pods_setup_content_types', pods_container_callback( 'pods.blocks', 'register_blocks' ) );
		add_filter( 'widget_types_to_hide_from_legacy_widget_block', pods_container_callback( 'pods.blocks', 'remove_from_legacy_widgets' ) );
	}
}
