<?php

require_once FLAMINGO_PLUGIN_DIR . '/admin/admin-functions.php';

add_action( 'admin_menu', 'flamingo_admin_menu', 8 );

function flamingo_admin_menu() {
	add_object_page(
		__( 'Flamingo Address Book', 'flamingo' ), __( 'Flamingo', 'flamingo' ),
		'flamingo_edit_contacts', 'flamingo', 'flamingo_contact_admin_page',
		flamingo_plugin_url( 'admin/images/menu-icon.png' ) );

	$contact_admin = add_submenu_page( 'flamingo',
		__( 'Flamingo Address Book', 'flamingo' ), __( 'Address Book', 'flamingo' ),
		'flamingo_edit_contacts', 'flamingo', 'flamingo_contact_admin_page' );

	add_action( 'load-' . $contact_admin, 'flamingo_load_contact_admin' );

	$inbound_admin = add_submenu_page( 'flamingo',
		__( 'Flamingo Inbound Messages', 'flamingo' ), __( 'Inbound Messages', 'flamingo' ),
		'flamingo_edit_inbound_messages', 'flamingo_inbound', 'flamingo_inbound_admin_page' );

	add_action( 'load-' . $inbound_admin, 'flamingo_load_inbound_admin' );
}

add_filter( 'set-screen-option', 'flamingo_set_screen_options', 10, 3 );

function flamingo_set_screen_options( $result, $option, $value ) {
	$flamingo_screens = array(
		'toplevel_page_flamingo_per_page',
		'flamingo_page_flamingo_inbound_per_page' );

	if ( in_array( $option, $flamingo_screens ) )
		$result = $value;

	return $result;
}

add_action( 'admin_enqueue_scripts', 'flamingo_admin_enqueue_scripts' );

function flamingo_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'flamingo' ) )
		return;

	wp_enqueue_style( 'flamingo-admin',
		flamingo_plugin_url( 'admin/style.css' ),
		array(), FLAMINGO_VERSION, 'all' );

	wp_enqueue_script( 'flamingo-admin',
		flamingo_plugin_url( 'admin/script.js' ),
		array( 'postbox' ), FLAMINGO_VERSION, true );

	$current_screen = get_current_screen();

	wp_localize_script( 'flamingo-admin', '_flamingo', array(
		'screenId' => $current_screen->id ) );
}

/* Updated Message */

add_action( 'flamingo_admin_updated_message', 'flamingo_admin_updated_message' );

function flamingo_admin_updated_message() {
	if ( ! empty( $_REQUEST['message'] ) ) {
		if ( 'contactupdated' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Contact updated.', 'flamingo' ) );
		elseif ( 'contactdeleted' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Contact deleted.', 'flamingo' ) );
		elseif ( 'inboundtrashed' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Messages trashed.', 'flamingo' ) );
		elseif ( 'inbounduntrashed' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Messages restored.', 'flamingo' ) );
		elseif ( 'inbounddeleted' == $_REQUEST['message'] )
			$updated_message = esc_html( __( 'Messages deleted.', 'flamingo' ) );
		else
			return;
	} else {
		return;
	}

	if ( empty( $updated_message ) )
		return;

?>
<div id="message" class="updated"><p><?php echo $updated_message; ?></p></div>
<?php
}

/* Contact */

function flamingo_load_contact_admin() {
	$action = flamingo_current_action();

	$redirect_to = admin_url( 'admin.php?page=flamingo' );

	if ( 'save' == $action && ! empty( $_REQUEST['post'] ) ) {
		$post = new Flamingo_Contact( $_REQUEST['post'] );

		if ( ! empty( $post ) ) {
			if ( ! current_user_can( 'flamingo_edit_contact', $post->id ) )
				wp_die( __( 'You are not allowed to edit this item.', 'flamingo' ) );

			check_admin_referer( 'flamingo-update-contact_' . $post->id );

			$post->props = (array) $_POST['contact'];

			$post->name = trim( $_POST['contact']['name'] );

			$post->tags = ! empty( $_POST['tax_input'][Flamingo_Contact::contact_tag_taxonomy] )
				? explode( ',', $_POST['tax_input'][Flamingo_Contact::contact_tag_taxonomy] )
				: array();

			$post->save();

			$redirect_to = add_query_arg( array(
				'action' => 'edit',
				'post' => $post->id,
				'message' => 'contactupdated' ), $redirect_to );
		}

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) )
			check_admin_referer( 'flamingo-delete-contact_' . $_REQUEST['post'] );
		else
			check_admin_referer( 'bulk-posts' );

		$deleted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Contact( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'flamingo_delete_contact', $post->id ) )
				wp_die( __( 'You are not allowed to delete this item.', 'flamingo' ) );

			if ( ! $post->delete() )
				wp_die( __( 'Error in deleting.', 'flamingo' ) );

			$deleted += 1;
		}

		if ( ! empty( $deleted ) )
			$redirect_to = add_query_arg( array( 'message' => 'contactdeleted' ), $redirect_to );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( ! empty( $_GET['export'] ) ) {
		$sitename = sanitize_key( get_bloginfo( 'name' ) );

		$filename = ( empty( $sitename ) ? '' : $sitename . '-' )
			. sprintf( 'flamingo-contact-%s.csv', date( 'Y-m-d' ) );

		header( 'Content-Description: File Transfer' );
		header( "Content-Disposition: attachment; filename=$filename" );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );

		$labels = array(
			__( 'Email', 'flamingo' ), __( 'Full name', 'flamingo' ),
			__( 'First name', 'flamingo' ), __( 'Last name', 'flamingo' ) );

		echo flamingo_csv_row( $labels );

		$args = array(
			'posts_per_page' => -1,
			'orderby' => 'meta_value',
			'order' => 'ASC',
			'meta_key' => '_email' );

		if ( ! empty( $_GET['s'] ) )
			$args['s'] = $_GET['s'];

		if ( ! empty( $_GET['orderby'] ) ) {
			if ( 'email' == $_GET['orderby'] )
				$args['meta_key'] = '_email';
			elseif ( 'name' == $_GET['orderby'] )
				$args['meta_key'] = '_name';
		}

		if ( ! empty( $_GET['order'] ) && 'asc' == strtolower( $_GET['order'] ) )
			$args['order'] = 'ASC';

		if ( ! empty( $_GET['contact_tag_id'] ) )
			$args['contact_tag_id'] = explode( ',', $_GET['contact_tag_id'] );

		$items = Flamingo_Contact::find( $args );

		foreach ( $items as $item ) {
			$row = array(
				$item->email,
				$item->get_prop( 'name' ),
				$item->get_prop( 'first_name' ),
				$item->get_prop( 'last_name' ) );

			echo "\r\n" . flamingo_csv_row( $row );
		}

		exit();
	}

	$post_id = ! empty( $_REQUEST['post'] ) ? $_REQUEST['post'] : '';

	if ( Flamingo_Contact::post_type == get_post_type( $post_id ) ) {
		add_meta_box( 'submitdiv', __( 'Save', 'flamingo' ),
			'flamingo_contact_submit_meta_box', null, 'side', 'core' );

		add_meta_box( 'contacttagsdiv', __( 'Tags', 'flamingo' ),
			'flamingo_contact_tags_meta_box', null, 'side', 'core' );

		add_meta_box( 'contactnamediv', __( 'Name', 'flamingo' ),
			'flamingo_contact_name_meta_box', null, 'normal', 'core' );

	} else {
		if ( ! class_exists( 'Flamingo_Contacts_List_Table' ) )
			require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/class-contacts-list-table.php';

		$current_screen = get_current_screen();

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'Flamingo_Contacts_List_Table', 'define_columns' ) );

		add_screen_option( 'per_page', array(
			'label' => __( 'Contacts', 'flamingo' ),
			'default' => 20 ) );
	}
}

function flamingo_contact_admin_page() {
	$action = flamingo_current_action();
	$post_id = ! empty( $_REQUEST['post'] ) ? $_REQUEST['post'] : '';

	if ( 'edit' == $action && Flamingo_Contact::post_type == get_post_type( $post_id ) ) {
		flamingo_contact_edit_page();
		return;
	}

	$list_table = new Flamingo_Contacts_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">
<?php screen_icon(); ?>

<h2><?php
	echo esc_html( __( 'Flamingo Address Book', 'flamingo' ) );

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'flamingo' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?></h2>

<?php do_action( 'flamingo_admin_updated_message' ); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Contacts', 'flamingo' ), 'flamingo-contact' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function flamingo_contact_edit_page() {
	$post = new Flamingo_Contact( $_REQUEST['post'] );

	if ( empty( $post ) )
		return;

	require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/meta-boxes.php';

	include FLAMINGO_PLUGIN_DIR . '/admin/edit-contact-form.php';
}

/* Inbound Message */

function flamingo_load_inbound_admin() {
	$action = flamingo_current_action();

	$redirect_to = admin_url( 'admin.php?page=flamingo_inbound' );

	if ( 'trash' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) )
			check_admin_referer( 'flamingo-trash-inbound-message_' . $_REQUEST['post'] );
		else
			check_admin_referer( 'bulk-posts' );

		$trashed = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'flamingo_delete_inbound_message', $post->id ) )
				wp_die( __( 'You are not allowed to move this item to the Trash.', 'flamingo' ) );

			if ( ! $post->trash() )
				wp_die( __( 'Error in moving to Trash.', 'flamingo' ) );

			$trashed += 1;
		}

		if ( ! empty( $trashed ) )
			$redirect_to = add_query_arg( array( 'message' => 'inboundtrashed' ), $redirect_to );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'untrash' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) )
			check_admin_referer( 'flamingo-untrash-inbound-message_' . $_REQUEST['post'] );
		else
			check_admin_referer( 'bulk-posts' );

		$untrashed = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'flamingo_delete_inbound_message', $post->id ) )
				wp_die( __( 'You are not allowed to restore this item from the Trash.', 'flamingo' ) );

			if ( ! $post->untrash() )
				wp_die( __( 'Error in restoring from Trash.', 'flamingo' ) );

			$untrashed += 1;
		}

		if ( ! empty( $untrashed ) )
			$redirect_to = add_query_arg( array( 'message' => 'inbounduntrashed' ), $redirect_to );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete_all' == $action ) {
		$_REQUEST['post'] = flamingo_get_all_ids_in_trash(
			Flamingo_Inbound_Message::post_type );

		$action = 'delete';
	}

	if ( 'delete' == $action && ! empty( $_REQUEST['post'] ) ) {
		if ( ! is_array( $_REQUEST['post'] ) )
			check_admin_referer( 'flamingo-delete-inbound-message_' . $_REQUEST['post'] );
		else
			check_admin_referer( 'bulk-posts' );

		$deleted = 0;

		foreach ( (array) $_REQUEST['post'] as $post ) {
			$post = new Flamingo_Inbound_Message( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'flamingo_delete_inbound_message', $post->id ) )
				wp_die( __( 'You are not allowed to delete this item.', 'flamingo' ) );

			if ( ! $post->delete() )
				wp_die( __( 'Error in deleting.', 'flamingo' ) );

			$deleted += 1;
		}

		if ( ! empty( $deleted ) )
			$redirect_to = add_query_arg( array( 'message' => 'inbounddeleted' ), $redirect_to );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	$post_id = ! empty( $_REQUEST['post'] ) ? $_REQUEST['post'] : '';

	if ( Flamingo_Inbound_Message::post_type == get_post_type( $post_id ) ) {
		add_meta_box( 'submitdiv', __( 'Save', 'flamingo' ),
			'flamingo_inbound_submit_meta_box', null, 'side', 'core' );

		add_meta_box( 'inboundfieldsdiv', __( 'Fields', 'flamingo' ),
			'flamingo_inbound_fields_meta_box', null, 'normal', 'core' );

	} else {
		if ( ! class_exists( 'Flamingo_Inbound_Messages_List_Table' ) )
			require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/class-inbound-messages-list-table.php';

		$current_screen = get_current_screen();

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'Flamingo_Inbound_Messages_List_Table', 'define_columns' ) );

		add_screen_option( 'per_page', array(
			'label' => __( 'Messages', 'flamingo' ),
			'default' => 20 ) );
	}
}

function flamingo_inbound_admin_page() {
	$action = flamingo_current_action();
	$post_id = ! empty( $_REQUEST['post'] ) ? $_REQUEST['post'] : '';

	if ( 'edit' == $action && Flamingo_Inbound_Message::post_type == get_post_type( $post_id ) ) {
		flamingo_inbound_edit_page();
		return;
	}

	$list_table = new Flamingo_Inbound_Messages_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">
<?php screen_icon(); ?>

<h2><?php
	echo esc_html( __( 'Inbound Messages', 'flamingo' ) );

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'flamingo' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?></h2>

<?php do_action( 'flamingo_admin_updated_message' ); ?>

<?php $list_table->views(); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Messages', 'flamingo' ), 'flamingo-inbound' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function flamingo_inbound_edit_page() {
	$post = new Flamingo_Inbound_Message( $_REQUEST['post'] );

	if ( empty( $post ) )
		return;

	require_once FLAMINGO_PLUGIN_DIR . '/admin/includes/meta-boxes.php';

	include FLAMINGO_PLUGIN_DIR . '/admin/edit-inbound-form.php';

}

?>