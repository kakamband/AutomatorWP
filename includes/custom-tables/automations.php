<?php
/**
 * Automations
 *
 * @package     AutomatorWP\Custom_Tables\Automations
 * @author      AutomatorWP <contact@automatorwp.com>, Ruben Garcia <rubengcdev@gmail.com>
 * @since       1.0.0
 */
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Parse query args for automations
 *
 * @since   1.0.0
 *
 * @param string $where
 * @param CT_Query $ct_query
 *
 * @return string
 */
function automatorwp_automations_query_where( $where, $ct_query ) {

    global $ct_table;

    if( $ct_table->name !== 'automatorwp_automations' ) {
        return $where;
    }

    $table_name = $ct_table->db->table_name;

    // Shorthand
    $qv = $ct_query->query_vars;

    // Status
    $where .= automatorwp_custom_table_where( $qv, 'status', 'string' );

    // User ID
    $where .= automatorwp_custom_table_where( $qv, 'user_id', 'integer' );

    return $where;
}
add_filter( 'ct_query_where', 'automatorwp_automations_query_where', 10, 2 );

/**
 * Define the search fields for automations
 *
 * @since 1.0.0
 *
 * @param array $search_fields
 *
 * @return array
 */
function automatorwp_automations_search_fields( $search_fields ) {

    $search_fields[] = 'title';

    return $search_fields;

}
add_filter( 'ct_query_automatorwp_automations_search_fields', 'automatorwp_automations_search_fields' );

/**
 * Parse search query args for automations
 *
 * @since 1.3.9.7
 *
 * @param string $search
 * @param CT_Query $ct_query
 *
 * @return string
 */
function automatorwp_automations_query_search( $search, $ct_query ) {

    global $ct_table, $wpdb;

    if( $ct_table->name !== 'automatorwp_automations' ) {
        return $search;
    }

    $table_name = $ct_table->db->table_name;

    // Shorthand
    $qv = $ct_query->query_vars;

    // Check if is search and query is not filtered by an specific user
    if( isset( $qv['s'] ) && ! empty( $qv['s'] ) && ! isset( $qv['user_id'] ) ) {

        // Made a user sub-search to retrieve them
        $users = get_users( array(
            'search' => sprintf( '*%s*', $qv['s'] ),
            'search_columns' => array(
                'user_login',
                'user_email',
                'display_name',
            ),
            'fields' => 'ID',
        ) );

        if( ! empty( $users ) ) {
            $search .= " AND ( {$table_name}.user_id IN (" . implode( ',', array_map( 'absint', $users ) ) . ") )";
        }

    }

    return $search;

}
add_filter( 'ct_query_search', 'automatorwp_automations_query_search', 10, 2 );

/**
 * Define the field for automations views
 *
 * @since 1.0.0
 *
 * @param string $field_id
 *
 * @return string
 */
function automatorwp_automations_views_field( $field_id = '' ) {
    return 'status';
}
add_filter( 'ct_list_automatorwp_automations_views_field', 'automatorwp_automations_views_field' );

/**
 * Define the field labels for automations views
 *
 * @since 1.0.0
 *
 * @param array $field_labels
 *
 * @return array
 */
function automatorwp_automations_views_field_labels( $field_labels = array() ) {
    return automatorwp_get_automation_statuses();
}
add_filter( 'ct_list_automatorwp_automations_views_field_labels', 'automatorwp_automations_views_field_labels' );

/**
 * Columns for automations list view
 *
 * @since 1.0.0
 *
 * @param array $columns
 *
 * @return array
 */
function automatorwp_manage_automations_columns( $columns = array() ) {

    $columns['title']       = __( 'Title', 'automatorwp' );
    $columns['triggers']    = __( 'Triggers', 'automatorwp' );
    $columns['actions']     = __( 'Actions', 'automatorwp' );
    $columns['user_id']     = __( 'Author', 'automatorwp' );
    $columns['status']      = __( 'Status', 'automatorwp' );
    $columns['date']        = __( 'Date', 'automatorwp' );

    return $columns;
}
add_filter( 'manage_automatorwp_automations_columns', 'automatorwp_manage_automations_columns' );

/**
 * Sortable columns for automations list view
 *
 * @since 1.0.0
 *
 * @param array $sortable_columns
 *
 * @return array
 */
function automatorwp_manage_automations_sortable_columns( $sortable_columns ) {

    $sortable_columns['title']      = array( 'title', false );
    $sortable_columns['user_id']    = array( 'user_id', false );
    $sortable_columns['status']     = array( 'status', false );
    $sortable_columns['date']       = array( 'date', true );

    return $sortable_columns;

}
add_filter( 'manage_automatorwp_automations_sortable_columns', 'automatorwp_manage_automations_sortable_columns' );

/**
 * Columns rendering for automations list view
 *
 * @since  1.0.0
 *
 * @param string $column_name
 * @param integer $object_id
 */
function automatorwp_manage_automations_custom_column(  $column_name, $object_id ) {

    // Setup vars
    $automation = ct_get_object( $object_id );

    switch( $column_name ) {
        case 'title':
            $title = ! empty( $automation->title ) ? $automation->title : __( '(No title)', 'automatorwp' ); ?>
                <strong><a href="<?php echo ct_get_edit_link( 'automatorwp_automations', $automation->id ); ?>"><?php echo $title; ?></a></strong>
            <?php

            break;
        case 'triggers':
            $triggers = automatorwp_get_automation_triggers( $automation->id );

            foreach( $triggers as $trigger ) {

                $type_args = automatorwp_get_trigger( $trigger->type );

                if( $type_args ) {
                    $integration = automatorwp_get_integration( $type_args['integration'] );

                    if( $integration ) : ?>

                        <div class="automatorwp-integration-icon">
                            <img src="<?php echo esc_attr( $integration['icon'] ); ?>" title="<?php echo esc_attr( $integration['label'] ); ?>" alt="<?php echo esc_attr( $integration['label'] ); ?>">
                        </div>

                    <?php endif;
                } else { ?>

                    <div class="automatorwp-integration-icon">
                        <img src="<?php echo esc_attr( AUTOMATORWP_URL . 'assets/img/integration-missing.svg' ); ?>" title="<?php echo esc_attr( __( 'Missing plugin', 'automatorwp' ) ); ?>">
                    </div>

                <?php }

                echo $trigger->title . '<br>';
            }

            break;
        case 'actions':
            $actions = automatorwp_get_automation_actions( $automation->id );

            foreach( $actions as $action ) {

                $type_args = automatorwp_get_action( $action->type );

                if( $type_args ) {
                    $integration = automatorwp_get_integration( $type_args['integration'] );

                    if( $integration ) : ?>

                        <div class="automatorwp-integration-icon">
                            <img src="<?php echo esc_attr( $integration['icon'] ); ?>" alt="<?php echo esc_attr( $integration['label'] ); ?>">
                        </div>

                    <?php endif;

                } else { ?>

                    <div class="automatorwp-integration-icon">
                        <img src="<?php echo esc_attr( AUTOMATORWP_URL . 'assets/img/integration-missing.svg' ); ?>" title="<?php echo esc_attr( __( 'Missing integration', 'automatorwp' ) ); ?>">
                    </div>

                <?php }

                echo $action->title . '<br>';
            }

            break;
        case 'user_id':
            $user = get_userdata( $automation->user_id );

            if( $user ) {

                if( current_user_can( 'edit_users' ) ) {
                    ?>

                    <a href="<?php echo get_edit_user_link( $user->ID ); ?>"><?php echo $user->display_name . ' (' . $user->user_login . ')'; ?></a>

                    <?php
                } else {
                    echo $user->display_name;
                }

            }
            break;
        case 'status':
            $statuses = automatorwp_get_automation_statuses();
            $status = isset( $statuses[$automation->status] ) ? $statuses[$automation->status] : $automation->status;
            ?>

            <span class="automatorwp-automation-status automatorwp-automation-status-<?php echo esc_attr( $automation->status ); ?>"><?php echo $status; ?></span>

            <?php

            break;
        case 'date':
            ?>

            <abbr title="<?php echo date( 'Y/m/d g:i:s a', strtotime( $automation->date ) ); ?>"><?php echo date( 'Y/m/d', strtotime( $automation->date ) ); ?></abbr>

            <?php
            break;
    }
}
add_action( 'manage_automatorwp_automations_custom_column', 'automatorwp_manage_automations_custom_column', 10, 2 );

/**
 * Default data when creating a new item (similar to WP auto draft) see ct_insert_object()
 *
 * @since  1.0.0
 *
 * @param array $default_data
 *
 * @return array
 */
function automatorwp_automations_default_data( $default_data = array() ) {

    $default_data['user_id']        = get_current_user_id();
    $default_data['sequential']     = 0;
    $default_data['times_per_user'] = 1;
    $default_data['times']          = 0;
    $default_data['status']         = 'inactive';
    $default_data['date']           = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );

    return $default_data;
}
add_filter( 'ct_automatorwp_automations_default_data', 'automatorwp_automations_default_data' );

/**
 * Turns array of date and time into a valid mysql date on update object data
 *
 * @since 1.0.0
 *
 * @param array $object_data
 * @param array $original_object_data
 *
 * @return array
 */
function automatorwp_automations_insert_automation_data( $object_data, $original_object_data ) {

    global $ct_table;

    // If not is our custom table, return
    if( $ct_table->name !== 'automatorwp_automations' ) {
        return $object_data;
    }

    // Fix date format
    if( isset( $object_data['date'] ) && ! empty( $object_data['date'] ) ) {
        $object_data['date'] = date( 'Y-m-d 00:00:00', strtotime( $object_data['date'] ) );
    }

    // Handle sequential saving
    $object_data['sequential'] = ( isset( $_POST['sequential'] ) ? 1 : 0 );

    return $object_data;

}
add_filter( 'ct_insert_object_data', 'automatorwp_automations_insert_automation_data', 10, 2 );

/**
 * Register custom CMB2 meta boxes
 *
 * @since  1.0.0
 */
function automatorwp_automations_meta_boxes( ) {

    // Title
    automatorwp_add_meta_box(
        'automatorwp-automations-title',
        __( 'Title', 'automatorwp' ),
        'automatorwp_automations',
        array(
            'title' => array(
                'name' 	=> __( 'Title', 'automatorwp' ),
                'type' 	=> 'text',
                'attributes' => array(
                    'placeholder' => __( 'Enter title here', 'automatorwp' ),
                )
            ),
        ),
        array(
            'priority' => 'core',
        )
    );

    // Save Changes
    automatorwp_add_meta_box(
        'automatorwp-automations-save-changes',
        __( 'Save Changes', 'automatorwp' ),
        'automatorwp_automations',
        array(
            'user_id' => array(
                'name' 	=> __( 'Author', 'automatorwp' ),
                'type' 	=> 'select',
                'classes' 	=> 'automatorwp-user-selector',
                'options_cb' => 'automatorwp_options_cb_users'
            ),
            'status' => array(
                'name' 	=> __( 'Status', 'automatorwp' ),
                'type' 	=> 'select',
                'options' => automatorwp_get_automation_statuses()
            ),
            'date' => array(
                'name' 	=> __( 'Date', 'automatorwp' ),
                'desc' 	=> __( 'Automation will take effect based on this date. You can schedule <strong>future</strong> automations by setting this field with a future date.', 'automatorwp' )
                    . '<br>' . __( '<strong>Note:</strong> For future automations, status need to be <strong>active</strong> because setting a future date won\'t update the status automatically.', 'automatorwp' ),
                'type' 	=> 'text_date_timestamp',
                'attributes' => array(
                    'autocomplete' => 'off'
                ),
                'classes' => 'automatorwp-has-tooltip',
                'after_field' => 'automatorwp_tooltip_cb',
                'after_row' => 'automatorwp_automations_publishing_actions'
            ),
        ),
        array(
            'priority' => 'default',
            'context' => 'side',
        )
    );

    // Completion Times
    automatorwp_add_meta_box(
        'automatorwp-automations-completion-times',
        __( 'Completion Times', 'automatorwp' ),
        'automatorwp_automations',
        array(
            'times_per_user' => array(
                'name' 	=> __( 'Times per user', 'automatorwp' ),
                'desc' 	=> __( 'Maximum number of times a user can complete this automation. Set it to 0 for unlimited.', 'automatorwp' ),
                'type' 	=> 'input',
                'attributes' => array(
                    'type' => 'number',
                    'min' => '0',
                ),
                'classes' => 'automatorwp-has-tooltip',
                'after_field' => 'automatorwp_tooltip_cb',
            ),
            'times' => array(
                'name' 	=> __( 'Total times', 'automatorwp' ),
                'desc' 	=> __( 'Maximum number of times this automation can be completed. Set it to 0 for unlimited.', 'automatorwp' ),
                'type' 	=> 'input',
                'attributes' => array(
                    'type' => 'number',
                    'min' => '0',
                ),
                'classes' => 'automatorwp-has-tooltip',
                'after_field' => 'automatorwp_tooltip_cb',
            ),
        ),
        array(
            'priority' => 'default',
            'context' => 'side',
        )
    );

}
add_action( 'cmb2_admin_init', 'automatorwp_automations_meta_boxes' );

/**
 * Publishing actions
 *
 * @since 1.0.0
 */
function automatorwp_automations_publishing_actions() {

    global $ct_table;

    if( ! $ct_table ) {
        return;
    }

    if( $ct_table->name !== 'automatorwp_automations' ) {
        return;
    }

    $primary_key = $ct_table->db->primary_key;
    $object_id = isset( $_GET[$primary_key] ) ? absint( $_GET[$primary_key] ) : 0;

    $automation = ct_get_object( $object_id );

    // Bail if automation doesn't exists
    if( ! $automation ) {
        return;
    }

    ?>
    <div id="major-publishing-actions">

        <?php if( $automation->status === 'inactive' ) : ?>

            <div class="automatorwp-save-and-activate">
                <?php submit_button( __( 'Save &amp; Activate' ), ' primary large', 'automatorwp-save-and-activate', false ); ?>
            </div>

            <div id="publishing-action">
                <?php submit_button( __( 'Save Changes' ), 'large', 'ct-save', false ); ?>
            </div>

            <div class="clear"></div>

        <?php else : ?>

            <div id="publishing-action">
                <?php submit_button( __( 'Save Changes' ), 'primary large', 'ct-save', false ); ?>
            </div>

        <?php endif; ?>

        <div id="delete-action">
            <?php
            printf(
                '<a href="%s" class="submitdelete deletion" onclick="%s" aria-label="%s">%s</a>',
                ct_get_delete_link( $ct_table->name, $object_id ),
                "return confirm('" .
                esc_attr( __( "Are you sure you want to delete this automation?\\n\\nClick \\'Cancel\\' to go back, \\'OK\\' to confirm the delete.", 'automatorwp' ) ) .
                "');",
                esc_attr( __( 'Delete permanently', 'automatorwp' ) ),
                __( 'Delete Permanently', 'automatorwp' )
            );
            ?>
        </div>

        <div class="clear"></div>

    </div>

    <?php
}

/**
 * Remove meta boxes
 *
 * @since  1.0.0
 */
function automatorwp_automations_remove_meta_boxes() {

    // Removes submitdiv box
    remove_meta_box( 'submitdiv', 'automatorwp_automations', 'side' );
}
add_action( 'add_meta_boxes', 'automatorwp_automations_remove_meta_boxes' );

/**
 * On delete an automation
 *
 * @since 1.0.0
 *
 * @param int $object_id
 */
function automatorwp_automations_delete_object( $object_id ) {

    global $wpdb, $ct_table;

    if( ! ( $ct_table instanceof CT_Table ) ) {
        return;
    }

    if( $ct_table->name !== 'automatorwp_automations' ) {
        return;
    }

    $logs       = AutomatorWP()->db->logs;
    $logs_meta 	= AutomatorWP()->db->logs_meta;

    // Delete all logs assigned to this action
    $wpdb->query( "DELETE l FROM {$logs} AS l WHERE l.object_id = {$object_id} AND l.type = 'automation'" );

    // Delete orphaned log metas
    $wpdb->query( "DELETE lm FROM {$logs_meta} lm LEFT JOIN {$logs} l ON l.id = lm.id WHERE l.id IS NULL" );

    // Delete all triggers
    $triggers = automatorwp_get_automation_triggers( $object_id );

    foreach( $triggers as $trigger ) {
        ct_setup_table( 'automatorwp_triggers' );

        ct_delete_object( $trigger->id );

        ct_reset_setup_table();
    }

    // Delete all actions
    $actions = automatorwp_get_automation_actions( $object_id );

    foreach( $actions as $action ) {
        ct_setup_table( 'automatorwp_actions' );

        ct_delete_object( $action->id );

        ct_reset_setup_table();
    }

}
add_action( 'delete_object', 'automatorwp_automations_delete_object' );