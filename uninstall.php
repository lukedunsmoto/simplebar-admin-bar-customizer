<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;
delete_option( 'sbabc_options' );
delete_option( 'sbabc_roles' );
