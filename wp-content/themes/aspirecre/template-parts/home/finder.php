<?php
/** Compatibility shim; renderer is owned by Aspire Core. */
defined( 'ABSPATH' ) || exit;
echo Aspire_Core_Blocks::render( 'finder', array(), false );
