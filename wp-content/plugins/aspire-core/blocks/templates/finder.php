<?php defined( 'ABSPATH' ) || exit; ?>
<section class="finder container" aria-labelledby="finder-heading">
	<div class="finder-intro"><h2 id="finder-heading">Find a property</h2><p>Search available properties across Greater Houston.</p></div>
	<form class="finder-form" action="<?php echo esc_url( home_url( '/properties/' ) ); ?>" method="get">
		<div><label for="property-type">Property Type</label><select id="property-type" name="property_type"><option value="">All property types</option><?php aspire_core_block_taxonomy_options( 'property_type' ); ?></select></div>
		<div><label for="transaction-type">Transaction Type</label><select id="transaction-type" name="transaction_type"><option value="">All transactions</option><?php aspire_core_block_taxonomy_options( 'transaction_type' ); ?></select></div>
		<div><label for="property-location">Location</label><input id="property-location" name="location" type="text" placeholder="City or area" autocomplete="address-level2"></div>
		<button class="button" type="submit">SEARCH <span aria-hidden="true">→</span></button>
	</form>
</section>
